<?php

namespace App;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{


    public function user()
    {
        $this->belongsTo(User::class, 'user_id', 'id');
    }
}
