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

//Route::get('/', function () {
//    return view('index');
//});

Route::resource('/', 'LocationsController');
Route::get('/location/{id}', 'LocationsController@detail')->name("detail");
Route::post('/search', 'LocationsController@search')->name("search");
Route::get('/about-us', 'LocationsController@aboutUs')->name("aboutUs");
