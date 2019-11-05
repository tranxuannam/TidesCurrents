<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
//});

Route::get('/tides/get_tide_current_by_date', 'Api\TidesCurrentsController@get_tide_current_by_date');
Route::get('/tides/get_prev_tide_current_by_date', 'Api\TidesCurrentsController@get_prev_tide_current_by_date');
Route::get('/tides/get_info_location', 'Api\TidesCurrentsController@get_info_location');
Route::get('/tides/get_near_locations', 'Api\TidesCurrentsController@get_near_locations');
