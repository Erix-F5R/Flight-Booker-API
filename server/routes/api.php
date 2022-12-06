<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AirlineController;
use App\Http\Controllers\AirportController;
use App\Http\Controllers\FlightController;



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

Route::apiResource('airlines', AirlineController::class);
Route::apiResource('airports', AirportController::class);
Route::apiResource('flights', FlightController::class);

Route::get('trips', function(Request $request){

    $flightQuery = new FlightController;
    
    return $flightQuery->test($request->airline);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
