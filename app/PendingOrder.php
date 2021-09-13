<?php

namespace App;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class PendingOrder extends Model
{
    protected $fillable = [
        'id',
        'order_id',
        'driver_id',
        'is_rejected',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
