<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
            setlocale(LC_TIME, "nl_NL");
            date_default_timezone_set('Europe/Amsterdam');
            Carbon::setLocale('nl');

            view()->composer('layouts.navbar', function($view) {
                $view->with('thisYear', Carbon::now()->format('Y'))
                     ->with('prevYear', Carbon::now()->subYear()->format('Y'))
                     ->with('nextYear', Carbon::now()->addYear()->format('Y'));
            });

            \Blade::if('admin', function() {
                return auth()->check() && auth()->user()->isAdmin();
            });

            // view()->composer('layouts.navbar', function($view) {
            //     $view->with('nextweek', Carbon::now()->addWeek()->format('d-m-Y'));
            // });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }
}
