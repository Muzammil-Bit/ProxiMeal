<?php

namespace App\Http\Controllers\API;

use App\Models\RestaurantsPayout;
use App\Repositories\RestaurantsPayoutRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
// use Flash;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Carbon;
use Laracasts\Flash\Flash;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\EarningRepository;

/**
 * Class RestaurantsPayoutController
 * @package App\Http\Controllers\API
 */

class RestaurantsPayoutAPIController extends Controller
{
    /** @var  RestaurantsPayoutRepository */
    private $restaurantsPayoutRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var RestaurantRepository
     */
    private $restaurantRepository;
    /**
     * @var EarningRepository
     */
    private $earningRepository;

    public function __construct(RestaurantsPayoutRepository $restaurantsPayoutRepo, CustomFieldRepository $customFieldRepo, RestaurantRepository $restaurantRepo, EarningRepository $earningRepository)
    {
        parent::__construct();
        $this->restaurantsPayoutRepository = $restaurantsPayoutRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->restaurantRepository = $restaurantRepo;
        $this->earningRepository = $earningRepository;
    }

    /**
     * Display a listing of the RestaurantsPayout.
     * GET|HEAD /restaurantsPayouts
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try{
            $this->restaurantsPayoutRepository->pushCriteria(new RequestCriteria($request));
            $this->restaurantsPayoutRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $restaurantsPayouts = $this->restaurantsPayoutRepository->all();

        return $this->sendResponse($restaurantsPayouts->toArray(), 'Restaurants Payouts retrieved successfully');
    }

    /**
     * Display the specified RestaurantsPayout.
     * GET|HEAD /restaurantsPayouts/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var RestaurantsPayout $restaurantsPayout */
        if (!empty($this->restaurantsPayoutRepository)) {
            $restaurantsPayout = $this->restaurantsPayoutRepository->findWithoutFail($id);
        }

        if (empty($restaurantsPayout)) {
            return $this->sendError('Restaurants Payout not found');
        }

        return $this->sendResponse($restaurantsPayout->toArray(), 'Restaurants Payout retrieved successfully');
    }
    
    public function store(Request $request)
    {
        $input = $request->all();
        $earning = $this->earningRepository->findByField('restaurant_id', $input['restaurant_id'])->first();
        if ($input['amount'] > $earning->restaurant_earning) {
            Flash::error('The payout amount must be less than restaurant earning');
            return redirect(route('restaurantsPayouts.create'))->withInput($input);
        }
        $input['paid_date'] = Carbon::now();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->restaurantsPayoutRepository->model());
        try {
            $this->earningRepository->update(['restaurant_earning' => $earning->restaurant_earning - $input['amount']], $earning->id);
            $restaurantsPayout = $this->restaurantsPayoutRepository->create($input);
            $restaurantsPayout->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        // Flash::success(__('lang.saved_successfully', ['operator' => __('lang.restaurants_payout')]));

        return $this->sendResponse("Data added success fully.", $input);
    }
    
    
}
