<?php

namespace App;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class OrderGroup extends Model
{
    protected $fillable = ['user_id', 'delivery_fee', 'driver_id'];

    function orders()
    {
        return $this->hasMany(Order::class, 'order_group_id');
    }
}
