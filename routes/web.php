<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

// index routes
Route::get('/', 'TaskController@index')->name('home');
Route::get('/home', 'TaskController@index')->name('home');

// custom logout route
Route::get('/logout' , 'Auth\LoginController@logout');

// main index routes
Route::get('/datum/{date}', 'TaskController@index');
Route::post('/date/search', 'TaskController@searchDate');

// create new task routes.
Route::get('/aanvraag/nieuw/{date}/{timeslot}', 'TaskController@create');
Route::post('/aanvraag/nieuw', 'TaskController@store');

// edit task routes.
Route::get('/aanvraag/{task}/bewerken', 'TaskController@edit');
Route::patch('/aanvraag/{task}', 'TaskController@update');

// admin routes.
Route::prefix('admin')->group(function () {
    Route::get('/', 'AdminController@index')->name('admin_index');
    Route::get('task/{task}', 'AdminController@show');
    Route::get('users/create', 'AdminController@createUser')->name('admin_create_user');
    Route::get('users/manage', 'AdminController@showUsers')->name('admin_show_users');
});

