<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Location;
use App\Models\Cart;
use App\Models\Restaurant;
use App\Models\Vehicle;

class DeliveryCostController extends Controller
{
    public function index($id, Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        $cart = Cart::where('user_id', $id)->get();
        // ** Check if cart exist in database
        if (sizeof($cart) == 0) {
            return Response(['message' => "Cart not found."], 400);
        }

        // ** Get USER Coordinates
        $userLat = $request->latitude;
        $userLong = $request->longitude;


        // ** Create Or Update location
        $userId = $cart[0]->user->id;
        $location = Location::where('user_id', $userId);
        if ($location != "" || $location != null) {
            $location->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        } else {
            $locationNew = new Location();
            $locationNew->user_id = $cart->user->id;
            $locationNew->latitude = $request->latitude;
            $locationNew->longitude = $request->longitude;
            $locationNew->save();
        }

        if (sizeof($cart) == 1) {
            $restaurant = $cart[0]->food->restaurant;
            $restaurant = Restaurant::find($restaurant->id);
            $food = $cart[0]->food;

            // ** Get Restaurant Coordinates
            $restLat = $restaurant->latitude;
            $restLong = $restaurant->longitude;
            $distance = $this->getDistance($restLat, $restLong, $userLat, $userLong);


            // ** Food Wieght
            $weight = $food->weight;
            $costs = array();
            array_push($costs, ["distance" => $distance]);

            // ** GET VEHICLES
            $vehicles = Vehicle::all();
            foreach ($vehicles as $vehicle) {
                if ($distance > $vehicle->maximum_range) {
                    array_push($costs, [
                        "cost" => null,
                        "vehicle_id" => $vehicle->id,
                        "vehicle_name" => $vehicle->type
                    ]);
                } else if ($distance < $vehicle->free_range) {
                    array_push($costs, [
                        'cost' => $vehicle->price * $distance,
                        "vehicle_id" => $vehicle->id,
                        "vehicle_name" => $vehicle->type
                    ]);
                } else if ($distance > $vehicle->free_range && $distance < $vehicle->maximum_range) {
                    array_push($costs, [
                        'cost' => $vehicle->price + ($vehicle->price + $vehicle->addiotional_cost_per_km_after_distance_limit) * $distance - $vehicle->free_range,
                        "vehicle_id" => $vehicle->id,
                        "vehicle_name" => $vehicle->type
                    ]);
                }
            }
            return $costs;
        } else {
            // !! Multi Cart 
            // ** Create an array of restaurants
            $restaurantsWithDistances = array();
            foreach ($cart as $item) {
                $restaurant = $item->food->restaurant;
                $restaurant = Restaurant::find($restaurant->id);
                // ** Get Restaurant Coordinates
                $restLat = $restaurant->latitude;
                $restLong = $restaurant->longitude;
                $distance = $this->getDistance($restLat, $restLong, $userLat, $userLong);

                array_push($restaurantsWithDistances, [
                    "restaurant_id" => $item->food->restaurant_id, "distance" => $this->getDistance($restLat, $restLong, $userLat, $userLong), // "latitude" => $restLat,// "longitude" => $restLong,
                ]);
            }
            $this->sort_array_of_array($restaurantsWithDistances, 'distance');
            $totalDistance = 0;
            foreach ($restaurantsWithDistances as $item) {
                $totalDistance += $item['distance'];
            }

            // ** GET VEHICLES
            $vehicles = Vehicle::all();
            $singleRiderCost = array();
            foreach ($vehicles as $vehicle) {
                if ($totalDistance > $vehicle->maximum_range) {
                    array_push($singleRiderCost, [
                        "cost" => null,
                        "vehicle_id" => $vehicle->id,
                        "vehicle_name" => $vehicle->type
                    ]);
                } else if ($totalDistance < $vehicle->free_range) {
                    array_push($singleRiderCost, [
                        'cost' => $vehicle->price * $totalDistance,
                        "vehicle_id" => $vehicle->id,
                        "vehicle_name" => $vehicle->type
                    ]);
                } else if ($totalDistance > $vehicle->free_range && $totalDistance < $vehicle->maximum_range) {
                    array_push($singleRiderCost, [
                        'cost' => $vehicle->price + ($vehicle->price + $vehicle->addiotional_cost_per_km_after_distance_limit) * $totalDistance - $vehicle->free_range,
                        "vehicle_id" => $vehicle->id,
                        "vehicle_name" => $vehicle->type
                    ]);
                }
            }
            $finalCosts = array();
            $finalCosts['single_rider'] = $singleRiderCost;



            $multiRiderCosts = array();
            // Multi Rider
            foreach ($restaurantsWithDistances as $item) {
                $tempArray = array();
                // $tempArray['restaurant_id'] = $item['restaurant_id'];
                foreach ($vehicles as $vehicle) {
                    if ($item['distance'] > $vehicle->maximum_range) {
                        array_push($tempArray, [
                            "cost" => null,
                            "vehicle_id" => $vehicle->id,
                            "vehicle_name" => $vehicle->type
                        ]);
                    } else if ($item['distance']  < $vehicle->free_range) {
                        array_push($tempArray, [
                            'cost' => $vehicle->price * $item['distance'],
                            "vehicle_id" => $vehicle->id,
                            "vehicle_name" => $vehicle->type
                        ]);
                    } else if ($item['distance']  > $vehicle->free_range && $item['distance']  < $vehicle->maximum_range) {
                        array_push($tempArray, [
                            'cost' => $vehicle->price + ($vehicle->price + $vehicle->addiotional_cost_per_km_after_distance_limit) * $item['distance']  - $vehicle->free_range,
                            "vehicle_id" => $vehicle->id,
                            "vehicle_name" => $vehicle->type
                        ]);
                    }
                }

                array_push($multiRiderCosts, $tempArray);
            }
            $finalCosts['multi_rider']  = $multiRiderCosts;
            return $finalCosts;
        }
    }



    function getDistance($point1_lat, $point1_long, $point2_lat, $point2_long, $unit = 'km', $decimals = 2)
    {
        // Calculate the distance in degrees
        $degrees = rad2deg(acos((sin(deg2rad($point1_lat)) * sin(deg2rad($point2_lat))) + (cos(deg2rad($point1_lat)) * cos(deg2rad($point2_lat)) * cos(deg2rad($point1_long - $point2_long)))));

        // Convert the distance in degrees to the chosen unit (kilometres, miles or nautical miles)
        switch ($unit) {
            case 'km':
                $distance = $degrees * 111.13384; // 1 degree = 111.13384 km, based on the average diameter of the Earth (12,735 km)
                break;
            case 'mi':
                $distance = $degrees * 69.05482; // 1 degree = 69.05482 miles, based on the average diameter of the Earth (7,913.1 miles)
                break;
            case 'nmi':
                $distance =  $degrees * 59.97662; // 1 degree = 59.97662 nautic miles, based on the average diameter of the Earth (6,876.3 nautical miles)
        }
        return round($distance, $decimals);
    }

    /*
    * Sorting the Distance Array
    */
    private function sort_array_of_array(&$array, $subfield)
    {
        $sortarray = array();
        foreach ($array as $key => $row) {
            $sortarray[$key] = $row[$subfield];
        }

        array_multisort($sortarray, SORT_ASC, $array);
    }
}
