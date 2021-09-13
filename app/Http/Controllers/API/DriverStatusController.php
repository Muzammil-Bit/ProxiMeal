<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Psr7\Response;

class DriverStatusController extends Controller
{
    public function update($id, Request $request)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);
        $user = User::find($id);
        if ($user == null || $user == "") {
            return Response(['message' => "User not found"], 404);
        }

        $user->driver->update(['available' => $request->status]);
        return Response(["message" => "Toggled Successfuly."], 200);
    }
}
