<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Location;
use App\Models\Driver;
use GuzzleHttp\Psr7\Response;
use App\Models\User;
use Exception;
use Psy\ExecutionLoop;

class LocationController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return "Index";
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric'
        ]);

        $user = User::find($request->id);

        if ($user == null || $user == []) {
            return Response(['message' => "User Not Found"], 401);
        }


        $location = Location::where('user_id', $request->id);

        if ($location->count() == 0) {
            $location->create([
                'user_id' => $request->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        } else {
            $location->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        }


        $location = $location->get();

        return Response([
            'data' => $location,
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $id = $request->id;

        $location = Location::where('user_id', $id);

        if ($location == null || $location == "") {
            return Response(['message' => 'Location Not found'], 404);
        }

        return $location;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
