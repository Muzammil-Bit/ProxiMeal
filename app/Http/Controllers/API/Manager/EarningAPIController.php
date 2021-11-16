<?php

namespace App\Http\Controllers\API\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\UploadRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\CustomFieldRepository;
use Prettus\Repository\Criteria\RequestCriteria;;

use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use App\Criteria\Restaurants\RestaurantsOfManagerCriteria;
use App\Models\Earning;

class EarningAPIController extends Controller
{
    /** @var  RestaurantRepository */
    private $restaurantRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private $uploadRepository;


    public function __construct(RestaurantRepository $restaurantRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo)
    {
        parent::__construct();
        $this->restaurantRepository = $restaurantRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
    }

    /**
     * Display a listing of the Restaurant.
     * GET|HEAD /restaurants
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->restaurantRepository->pushCriteria(new RequestCriteria($request));
            $this->restaurantRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->restaurantRepository->pushCriteria(new RestaurantsOfManagerCriteria(auth()->id()));
            $restaurants = $this->restaurantRepository->get(['id', 'name']);
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        // return $restaurants;
        foreach ($restaurants as $rest) {
            $rest['earnings'] = array();
            $earnings = Earning::where('restaurant_id', $rest['id'])->get();
            $rest['earnings'] = $earnings;
        }

        return $this->sendResponse($restaurants, "REST");
    }
}
