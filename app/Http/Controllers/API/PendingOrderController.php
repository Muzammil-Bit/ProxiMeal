<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\PendingOrder;
use App\Models\User;

class PendingOrderController extends Controller
{
    public function accept($id)
    {
        $pendingOrder = PendingOrder::where('is_accepted', 0)->where('order_id', $id)->first();
        if (empty($pendingOrder)) {
            return $this->sendError("Order not found.");
        }

        $pendingOrder->driver_id = auth()->user()->id;
        $pendingOrder->is_accepted = 1;
        $pendingOrder->is_rejected = 0;

        $order = Order::where('id', $pendingOrder->order_id)->update([
            'driver_id' => auth()->user()->id,
        ]);
        $pendingOrder->save();

        return $this->sendResponse($order, "Order accepted successfully.");
    }

    public function reject($id)
    {
        $pendingOrder = PendingOrder::where('is_accepted', 0)->where('order_id', $id)->where('is_rejected', 0)->first();
        if ($pendingOrder == null || $pendingOrder == '') {
            return $this->sendError("Order Not Found");
        }

        $pendingOrder->is_rejected = 1;
        $pendingOrder->is_accepted = 0;
        $pendingOrder->save();

        return $this->sendResponse($pendingOrder, "Order Rejected Successfully");
    }

    public function notifications($id)
    {
        $user = User::findOrFail($id);
        $pendings = PendingOrder::where('driver_id', $user->id)->where('is_accepted', 0)->where('is_rejected', 0)->get();
        if (count($pendings) == 0) {
            return $this->sendError("No new notifications");
        }

        $pendingOrders = array();
        foreach ($pendings as $pending) {
            $order = Order::find($pending->order_id);
            $user = User::find($order->user_id);
            $userName = $user->name;

            if (sizeof($order->foods) != 0) {
                array_push($pendingOrders, [
                    'order_id' => $order->id,
                    'user_name' => $userName,
                    'delivery_address' => $user->delivery_address,
                    'user_email' => $user->email,
                    'user_lattitude' => $user->location->latitude,
                    'user_longitude' => $user->location->longitude,
                    'restaurant' => $order->foods[0]->restaurant,
                ]);
            }
        }
        return $this->sendResponse($pendingOrders, "Notifications retrieved successfully.");
    }
}
