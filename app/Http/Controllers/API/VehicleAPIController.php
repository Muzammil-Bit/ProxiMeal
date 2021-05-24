<?php

namespace App\Http\Controllers\API;


use App\Models\Vehicle;
use App\Repositories\VehicleRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
use Flash;

/**
 * Class VehicleController
 * @package App\Http\Controllers\API
 */

class VehicleAPIController extends Controller
{
    /** @var  VehicleRepository */
    private $vehicleRepository;

    public function __construct(VehicleRepository $vehicleRepo)
    {
        $this->vehicleRepository = $vehicleRepo;
    }

    /**
     * Display a listing of the Vehicle.
     * GET|HEAD /vehicles
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try{
            $this->vehicleRepository->pushCriteria(new RequestCriteria($request));
            $this->vehicleRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        $vehicle = $this->vehicleRepository->all();

        return $this->sendResponse($vehicle->toArray(), 'Vehicles retrieved successfully');
    }

    /**
     * Display the specified Vehicle.
     * GET|HEAD /vehicles/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var Vehicle $driver */
        if (!empty($this->vehicleRepository)) {
            $vehicle = $this->vehicleRepository->findWithoutFail($id);
        }

        if (empty($vehicle)) {
            return $this->sendError('Vehicle not found');
        }

        return $this->sendResponse($vehicle->toArray(), 'Vehicle retrieved successfully');
    }
}