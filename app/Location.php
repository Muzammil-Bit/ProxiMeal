<?php

namespace App;

/* 
@Auhor : Muzammil Hussain
*/

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'longitude',
        'latitude',
    ];







    public function driver()
    {
        return $this->belongsTo(User::class);
    }
}
