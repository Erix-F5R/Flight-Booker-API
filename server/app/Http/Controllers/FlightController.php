<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Flight;
use Illuminate\Http\Request;


class Trip 
{
    public $price = 0.0;
    public $departure_flights = array();
    public $return_flights = array();

    public function updatePrice($price){
        $this->price = $this->price + (float) $price;        
    }

    public function addDepartureFlight($flight){

        $this->departure_flights[] = $flight;

    }

    public function addReturnFlight($flight){

        $this->return_flights[] = $flight;

    }



}

class FlightController extends Controller
{   


    public function getByAirportcode($departure_airport, $arrival_airport)
    {

        $flights = Flight::where("departure_airport", $departure_airport)->where("arrival_airport", $arrival_airport)->get();

        $aTrip = new Trip;
        $aTrip->updatePrice(100);
        $aTrip->updatePrice(33);
        $aTrip->updatePrice(10);

        $aTrip->addDepartureFlight('Flight1');
        $aTrip->addDepartureFlight('Flight2');

        return  json_encode($aTrip);

    }

    public function getOnewayTrips($departure_airport, $arrival_airport, $departure_date){

        $flights = Flight::where("departure_airport", $departure_airport)->where("arrival_airport", $arrival_airport)->get();
        $trips = array();

        foreach($flights as $flight){
            $trip = new Trip;
            $flight['date'] = $departure_date;
            $trip->updatePrice($flight->price);
            $trip->addDepartureFlight($flight);
            $trips[] = $trip;
        }

        return array("trips" => $trips);


    }

    public function getRoundTrips($departure_airport, $arrival_airport, $departure_date, $return_date){
        // $flights = DB::table('flights')
        // ->join('airports AS a1', 'flights.arrival_airport', '=' , 'a1.code')
        // ->join('airports AS a2', 'flights.departure_airport', '=' , 'a2.code')
        // ->select('flights.*', 'a1.name AS arrival_name', 'a2.name AS departure_name')
        // ->get();

        $flights = DB::table('flights AS departure')
            ->join('flights AS return', 'departure.arrival_airport', '=', 'return.departure_airport')
            ->whereColumn('return.airline', 'departure.airline')
            ->where('departure.departure_airport', $departure_airport)
            ->where('departure.arrival_airport', $arrival_airport)
            ->select('return.number AS return_flight_num', 'return.airline AS return_airline', 'departure.number as departure_flight_num', 'departure.airline as departure_airline')
            ->get();

        $trips = array();

        foreach($flights as $flight){
            $trip = new Trip;
            //Get departure fight
            $departure_flight = Flight::where('airline', $flight->departure_airline)->where('number', $flight->departure_flight_num)->first();
            $departure_flight['date'] = $departure_date;
            $trip->updatePrice($departure_flight->price);
            $trip->addDepartureFlight($departure_flight);

            //Get return flight
            $return_flight = Flight::where('airline', $flight->return_airline)->where('number', $flight->return_flight_num)->first();
            $return_flight['date'] = $return_date;
            $trip->updatePrice($return_flight->price);
            $trip->addReturnFlight($return_flight);

            $trips[] = $trip;
        }

        return $trips;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $flights = Flight::all();

        return $flights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $flight = new Flight;

        $flight->airline = $request->airline;
        $flight->number = $request->number;
        $flight->departure_time = $request->departure_time;
        $flight->departure_airport = $request->departure_airport;
        $flight->arrival_time = $request->arrival_time;
        $flight->arrival_airport = $request->arrival_airport;
        $flight->price = $request->price;

        $flight->save();

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Flight  $flight
     * @return \Illuminate\Http\Response
     */
    public function show(Flight $flight)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Flight  $flight
     * @return \Illuminate\Http\Response
     */
    public function edit(Flight $flight)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Flight  $flight
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Flight $flight)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Flight  $flight
     * @return \Illuminate\Http\Response
     */
    public function destroy(Flight $flight)
    {
        $flight->delete();
        // $flightDelete = Flight::where('id',$flight)->first();
        // $flightDelete()->delete();

    }
}