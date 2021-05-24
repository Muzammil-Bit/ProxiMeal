<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Eloquent as Model;
use Illuminate\Support\Facades\DB;

class RoleFolio extends Model
{
    protected $table = 'roles_folio';
    public $timestamps = false;

    public $fillable = [
        'role_id',
        'prefix',
        'next',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'prefix' => 'string',
        'next' => 'integer'
    ];
}