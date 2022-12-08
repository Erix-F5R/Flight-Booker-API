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

    public function updatePrice($price)
    {
        $this->price = $this->price + (float) $price;
    }

    public function addDepartureFlight($flight)
    {

        $this->departure_flights[] = $flight;

    }

    public function addReturnFlight($flight)
    {

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

        return json_encode($aTrip);

    }

    public function getOnewayTrips($departure_airport, $arrival_airport, $departure_date)
    {

        //Case 1: One way Direct flights
        $flights = Flight::where("departure_airport", $departure_airport)->where("arrival_airport", $arrival_airport)->get();
        $trips = array();

        foreach ($flights as $flight) {
            $trip = new Trip;
            $flight['date'] = $departure_date;
            $trip->updatePrice($flight->price);
            $trip->addDepartureFlight($flight);
            $trips[] = $trip;
        }

        //Case 3: One way connecting flights

        $connecting_flights = DB::table('flights as leg_1')
            ->join('flights as leg_2', 'leg_1.arrival_airport', 'leg_2.departure_airport')
            ->whereColumn('leg_1.airline', 'leg_2.airline')
            ->where('leg_1.departure_airport', $departure_airport)
            ->where('leg_2.arrival_airport', $arrival_airport)
            ->select('leg_1.airline AS leg_1_airline', 'leg_2.airline AS leg_2_airline', 'leg_1.number AS leg_1_number', 'leg_2.number AS leg_2_number')
            ->get();


        foreach ($connecting_flights as $flight) {
            $trip = new Trip;

            $leg_1_flight = Flight::where('airline', $flight->leg_1_airline)->where('number', $flight->leg_1_number)->first();
            $leg_1_flight['date'] = $departure_date;
            $trip->updatePrice($leg_1_flight->price);
            $trip->addDepartureFlight($leg_1_flight);

            $leg_2_flight = Flight::where('airline', $flight->leg_2_airline)->where('number', $flight->leg_2_number)->first();
            $leg_2_flight['date'] = $departure_date;
            $trip->updatePrice($leg_2_flight->price);
            $trip->addDepartureFlight($leg_2_flight);

            $trips[] = $trip;

        }


        return array("trips" => $trips);


    }

    public function getRoundTrips($departure_airport, $arrival_airport, $departure_date, $return_date)
    {

        //Case 2: Direct Flights
        $flights = DB::table('flights AS departure')
            ->join('flights AS return', 'departure.arrival_airport', '=', 'return.departure_airport')
            ->whereColumn('return.airline', 'departure.airline')
            ->where('departure.departure_airport', $departure_airport)
            ->where('departure.arrival_airport', $arrival_airport)
            ->select('return.number AS return_flight_num', 'return.airline AS return_airline', 'departure.number as departure_flight_num', 'departure.airline as departure_airline')
            ->get();

        $trips = array();

        foreach ($flights as $flight) {
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


        //Case 3: Connecting Flights

        $connecting_flights = DB::table('flights as departure_1')
            ->join('flights as departure_2', 'departure_1.arrival_airport', 'departure_2.departure_airport')
            ->join('flights as return_1', 'departure_2.arrival_airport', 'return_1.departure_airport')
            ->join('flights as return_2', 'return_1.arrival_airport', 'return_2.departure_airport')
            ->whereColumn('departure_1.airline', 'departure_2.airline')
            ->whereColumn('return_1.airline', 'return_2.airline')
            ->whereColumn('departure_1.airline', 'return_1.airline')
            ->where('departure_1.departure_airport', $departure_airport)
            ->where('departure_2.arrival_airport', $arrival_airport)
            ->where('return_2.arrival_airport', $departure_airport)
            ->select(
                'departure_1.airline',
                'departure_1.number AS departure_1_number',
                'departure_2.number AS departure_2_number',
                'return_1.number AS return_1_number',
                'return_2.number AS return_2_number',
            )
            ->get();

        foreach ($connecting_flights as $flight) {
            $trip = new Trip;

            //Get departure fights
            $departure_1 = Flight::where('airline', $flight->airline)->where('number', $flight->departure_1_number)->first();
            $departure_1['date'] = $departure_date;
            $trip->updatePrice($departure_1->price);
            $trip->addDepartureFlight($departure_1);

            $departure_2 = Flight::where('airline', $flight->airline)->where('number', $flight->departure_2_number)->first();
            $departure_2['date'] = $departure_date;
            $trip->updatePrice($departure_2->price);
            $trip->addDepartureFlight($departure_2);

            //get return flights

            $return_1 = Flight::where('airline', $flight->airline)->where('number', $flight->return_1_number)->first();
            $return_1['date'] = $departure_date;
            $trip->updatePrice($return_1->price);
            $trip->addReturnFlight($return_1);

            $return_2 = Flight::where('airline', $flight->airline)->where('number', $flight->return_2_number)->first();
            $return_2['date'] = $departure_date;
            $trip->updatePrice($return_2->price);
            $trip->addReturnFlight($return_2);

            $trips[] = $trip;

        }


        return array("trips" => $trips);
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