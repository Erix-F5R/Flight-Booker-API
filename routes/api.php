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

Route::get('trips', function (Request $request) {

    $flightQuery = new FlightController;
    

    //Check the type of trip 
    //and call the appropriate function inside the FlightController
    if($request->trip_type == 'one_way'){
        return $flightQuery->getOnewayTrips($request->departure_airport, $request->arrival_airport, $request->departure_date);
    }else{
        return $flightQuery->getRoundTrips($request->departure_airport, $request->arrival_airport, $request->departure_date, $request->return_date);
    }

});

Route::get('test', function () {

    return 'test';
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});