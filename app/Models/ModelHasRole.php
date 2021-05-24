<?php

namespace App\Models;

use Eloquent as Model;

class ModelHasRole extends Model
{
    protected $table = 'model_has_roles';
    public $timestamps = false;

    public $fillable = [
        'role_id',
        'model_type',
        'model_id',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'model_type' => 'string',
        'model_id' => 'integer'
    ];
}