<?php

namespace App\Repositories;

use App\Models\RoleFolio;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class RoleFolioRepository
*/
class RoleFolioRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'role_id',
        'prefix',
        'next'
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return RoleFolio::class;
    }
}