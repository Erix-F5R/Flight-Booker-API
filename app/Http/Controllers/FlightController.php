<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Flight;
use Illuminate\Http\Request;


//This is a utility class to hold my flights and sum the price of the trip
//The API returns a collection of trips
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


    public function getOnewayTrips($departure_airport, $arrival_airport, $departure_date)
    {
        //
        //Case 1: One way Direct flights
        //

        //Here I am using Eloquents ORM because to just query flights that match the dep/arrival airports
        $flights = Flight::where("departure_airport", $departure_airport)->where("arrival_airport", $arrival_airport)->get();
        $trips = array();

        foreach ($flights as $flight) {
            $trip = new Trip;
            $flight['date'] = $departure_date;
            $trip->updatePrice($flight->price);
            $trip->addDepartureFlight($flight);
            $trips[] = $trip;
        }


        //
        //Case 3: One way connecting flights
        //

        //I swtiched from querying with the ORM to SQL
        //SQL gave me more power/flexiblity with my joins 
        $connecting_flights = DB::table('flights as leg_1')
        //Join on arrival = departure airport
            ->join('flights as leg_2', 'leg_1.arrival_airport', 'leg_2.departure_airport')
            //For my project I don't allow cross airline flights
            ->whereColumn('leg_1.airline', 'leg_2.airline')
            //Now I eleminate all trips that don't correspond to the dep/arr airport
            ->where('leg_1.departure_airport', $departure_airport)
            ->where('leg_2.arrival_airport', $arrival_airport)
            //I couldn't find a good way to Alias the table to I'm just passing the airline and flight numbers
            ->select('leg_1.airline', 'leg_1.number AS leg_1_number', 'leg_2.number AS leg_2_number')
            ->get();


        //I loop through all my flight numbers and query with ORM to find the flight
        //Then I add it to the trip
        foreach ($connecting_flights as $flight) {
            $trip = new Trip;

            $leg_1_flight = Flight::where('airline', $flight->airline)->where('number', $flight->leg_1_number)->first();
            $leg_1_flight['date'] = $departure_date;
            $trip->updatePrice($leg_1_flight->price);
            $trip->addDepartureFlight($leg_1_flight);

            $leg_2_flight = Flight::where('airline', $flight->airline)->where('number', $flight->leg_2_number)->first();
            $leg_2_flight['date'] = $departure_date;
            $trip->updatePrice($leg_2_flight->price);
            $trip->addDepartureFlight($leg_2_flight);

            //Check that the times line up for the connection
            if (strtotime($leg_1_flight->arrival_time) < strtotime($leg_2_flight->departure_time)) {
                $trips[] = $trip;
            }



        }


        return array("trips" => $trips);


    }

    //Case 2 and 4 follow a similar but expanded logic as Case 3
    public function getRoundTrips($departure_airport, $arrival_airport, $departure_date, $return_date)
    {
        //
        //Case 2: Round Trip Direct Flights
        //

        $flights = DB::table('flights AS departure')
            ->join('flights AS return', 'departure.arrival_airport', '=', 'return.departure_airport')
            ->whereColumn('return.airline', 'departure.airline')
            ->where('departure.departure_airport', $departure_airport)
            ->where('departure.arrival_airport', $arrival_airport)
            ->select('return.number AS return_flight_num', 'return.airline', 'departure.number as departure_flight_num')
            ->get();

        $trips = array();

        foreach ($flights as $flight) {
            $trip = new Trip;
            //Get departure fight
            $departure_flight = Flight::where('airline', $flight->airline)->where('number', $flight->departure_flight_num)->first();
            $departure_flight['date'] = $departure_date;
            $trip->updatePrice($departure_flight->price);
            $trip->addDepartureFlight($departure_flight);

            //Get return flight
            $return_flight = Flight::where('airline', $flight->airline)->where('number', $flight->return_flight_num)->first();
            $return_flight['date'] = $return_date;
            $trip->updatePrice($return_flight->price);
            $trip->addReturnFlight($return_flight);

            $trips[] = $trip;
        }


        //Case 3: Connecting Flights

        //I limit the connections to 1... so only 2 legs in each direction
        $connecting_flights = DB::table('flights as departure_1')
            ->join('flights as departure_2', 'departure_1.arrival_airport', 'departure_2.departure_airport')
            ->join('flights as return_1', 'departure_2.arrival_airport', 'return_1.departure_airport')
            ->join('flights as return_2', 'return_1.arrival_airport', 'return_2.departure_airport')
            ->whereColumn('departure_1.airline', 'departure_2.airline')
            ->whereColumn('return_1.airline', 'return_2.airline')
            ->whereColumn('departure_1.airline', 'return_1.airline')

            //Here I elminate flights that are the wrong origin/destination
            //Or that don't go anywhere such as YUL->YYZ->YUL and back via YUL->YYZ->YUL
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
            $return_1['date'] = $return_date;
            $trip->updatePrice($return_1->price);
            $trip->addReturnFlight($return_1);

            $return_2 = Flight::where('airline', $flight->airline)->where('number', $flight->return_2_number)->first();
            $return_2['date'] = $return_date;
            $trip->updatePrice($return_2->price);
            $trip->addReturnFlight($return_2);

            //Check that the connections in both directions are possible
            if (
                strtotime($departure_1->arrival_time) < strtotime($departure_2->departure_time)
                && strtotime($return_1->arrival_time) < strtotime($return_2->departure_time)
            ) {

                $trips[] = $trip;
            }

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