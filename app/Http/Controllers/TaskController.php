<?php

namespace App\Http\Controllers;

use App\Classes\EmailDateFormatter;
use App\Task;
use App\Absence;
use App\Timetable;
use Carbon\Carbon;
use App\Mail\EditedTask;
use App\Classes\Weekdays;
use App\Mail\NewTaskRequest;
use Illuminate\Http\Request;
use App\Classes\AttachmentHandler;
use Illuminate\Support\Facades\Mail;

class TaskController extends Controller
{
    private $today;

    public function __construct()
    {
        $this->middleware('auth');
        $this->today = Carbon::parse('now')->toDateString();
    }


    public function index(string $date = 'now')
    {
        // determine which dates to show.
            $today = $this->today;

            $week = new Weekdays($date);
            $weekdays = $week->getDaysofWeek();
            $date_back = $week->getPreviousWeek();
            $date_forward = $week->getNextWeek();

        // Retrieve timeslots.
        $timeslots = Timetable::all();

        // Retrieve asbence
        $absences = Absence::all();

        // Separate tasks into resp. days and timeslots.
        return view('tasks.index', compact('timeslots', 'weekdays', 'date_back', 'date_forward', 'today', 'absences'));
    }

    public function searchDate()
    {
        $date = request('date-DayMonth').'-'.request('date-Year');

        if($date == "") {
            $date = Carbon::now()->format('d-m-Y');
        }

        $this->validate(request(), [
            'date' => 'date'
        ]);

        return redirect('/datum/'.$date);
    }

public function filter(string $date = 'now')
    {
       // today
        $today = Carbon::parse('now')->toDateString();

        // determine which dates to show.
            $week = new Weekdays($date);
            $weekdays = $week->getDaysofWeek();
            $date_back = $week->getPreviousWeek();
            $date_forward = $week->getNextWeek();

        // Retrieve timeslots.
        $timeslots = Timetable::all();
        $tasks = Task::all();

        // Separate tasks into resp. days and timeslots.
        return view('tasks.filter', compact('timeslots', 'weekdays', 'date_back', 'date_forward', 'today', 'tasks'));

    }

    public function create($date, $timeslot)
    {

        $today = $this->today;

        if(Carbon::parse($date) < Carbon::parse($today)) {
            return redirect('/');
        }

        $absenceArray = Absence::getArray($date);

        if(in_array($timeslot, $absenceArray)) {
            return redirect('/');
        }

        $timetable = Timetable::all();
        $notAvailable = Task::where('date', $date)->where('timetable_id', $timeslot)->pluck('type')->search('assistentie');

        return view('tasks.create', compact('date', 'timeslot', 'timetable', 'notAvailable'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:25',
            'body' => 'required',
            'type' => 'required',
            'class' => 'required',
            'subject' => 'required',
            'location' => 'required'
        ]);

        $user = auth()->user();
        $task = $user->submit(
            new Task(request(['date', 'timetable_id', 'title', 'body', 'type', 'class', 'subject', 'location']))
        );

        $actionURL = env('APP_URL').'/admin/task/'.$task->id;

        // format the date and time
            $day = EmailDateFormatter::getWeekdayMonth($request->date);
            $time = EmailDateFormatter::getSchoolTime($request->timetable_id);

        // upload files and send email
            $filepath = (new AttachmentHandler($request))->uploadAttachment();
            Mail::to(env('APP_ADMIN_EMAIL'))
                    ->later(3, new NewTaskRequest($user, $task, $actionURL, $time, $day, $filepath));

        // redirect user with success flash
        session()->flash('message', 'Aanvraag succesvol ingediend');

        // generate redirect URL based on booked timeslot
        $redirectURL = EmailDateFormatter::getRedirectURL($request->timetable_id, $request->date);

        return redirect($redirectURL);

    }

    public function edit(Task $task)
    {
        if($task->user_id !== auth()->id()) {
            return redirect('/');
        }

        $today = Carbon::parse('now')->toDateString();

        if(Carbon::parse($task->date) < Carbon::parse($today)) {
            return redirect('/');
        }

        $timetable = Timetable::all();
        $notAvailable = Task::where('date', $task->date)->where('timetable_id', $task->timetable_id)->pluck('type')->search('assistentie');

        return view('tasks.edit', compact('task', 'timetable', 'notAvailable'));
    }

    public function update(Request $request, Task $task)
    {

        if(auth()->id() !== $task->user_id) {
            return redirect('/');
        }

        $user = auth()->user();

        $request->validate([
            'title' => 'required|max:25',
            'body' => 'required',
            'type' => 'required',
            'class' => 'required',
            'subject' => 'required',
            'location' => 'required'
        ]);

        $task->title = request('title');
        $task->body = request('body');
        $task->type = request('type');
        $task->class = request('class');
        $task->subject = request('subject');
        $task->location = request('location');
        $task->accepted = 2;
        $task->save();

        $date = Carbon::parse(request('date'))->startOfWeek()->format('d-m-Y');

        // send an e-mail to the site-admin
        $actionURL = env('APP_URL').'/admin/task/'.$task->id;

        // format the date and time
        $day = EmailDateFormatter::getWeekdayMonth($request->date);
        $time = EmailDateFormatter::getSchoolTime($request->timetable_id);

        // upload files and send email
        $filepath = (new AttachmentHandler($request))->uploadAttachment();
        Mail::to(env('APP_ADMIN_EMAIL'))
            ->later(5, new EditedTask($user, $task, $actionURL, $time, $day, $filepath));

    // redirect user with success flash
        session()->flash('message', 'Wijzigingen zijn opgeslagen.');

        return redirect('/datum/'.$date);

    }


    public function destroy(Task $task)
    {
        if(auth()->id() !== $task->user_id) {
            return redirect('/');
        }

        $date = Carbon::parse($task->date)->startOfWeek()->format('d-m-Y');
        $task->delete();

        // redirect user with success flash
        session()->flash('message', 'De aanvraag is verwijderd.');

        return redirect('/datum/'.$date);

    }

}
