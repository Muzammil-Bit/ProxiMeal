<?php

/**
 * File name: OrderController.php
 * Last modified: 2020.06.08 at 20:36:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 */

namespace App\Http\Controllers;

use App\Criteria\Orders\OrdersOfUserCriteria;
use App\Criteria\Users\ClientsCriteria;
use App\Criteria\Users\DriversCriteria;
use App\Criteria\Users\DriversOfRestaurantCriteria;
use App\DataTables\OrderDataTable;
use App\DataTables\FoodOrderDataTable;
use App\Events\OrderChangedEvent;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Mail\EmailOrder;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Restaurant;
use App\Notifications\AssignedOrder;
use App\Notifications\StatusChangedOrder;
use App\Repositories\CustomFieldRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\OrderStatusRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;
use App\Models\User;
use App\Notifications\AcceptOrRejectOrder;
use App\OrderGroup;
use App\PendingOrder;

class OrderController extends Controller
{
    /** @var  OrderRepository */
    private $orderRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /** @var  NotificationRepository */
    private $notificationRepository;
    /** @var  PaymentRepository */
    private $paymentRepository;

    public function __construct(
        OrderRepository $orderRepo,
        CustomFieldRepository $customFieldRepo,
        UserRepository $userRepo,
        OrderStatusRepository $orderStatusRepo,
        NotificationRepository $notificationRepo,
        PaymentRepository $paymentRepo
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->userRepository = $userRepo;
        $this->orderStatusRepository = $orderStatusRepo;
        $this->notificationRepository = $notificationRepo;
        $this->paymentRepository = $paymentRepo;
    }

    /**
     * Display a listing of the Order.
     *
     * @param OrderDataTable $orderDataTable
     * @return Response
     */
    public function index(OrderDataTable $orderDataTable)
    {
        return $orderDataTable->render('orders.index');
    }

    /**
     * Show the form for creating a new Order.
     *
     * @return Response
     */
    public function create()
    {
        $user = $this->userRepository->getByCriteria(new ClientsCriteria())->pluck('name', 'id');
        $driver = $this->userRepository->getByCriteria(new DriversCriteria())->pluck('name', 'id');

        $orderStatus = $this->orderStatusRepository->pluck('status', 'id');

        $hasCustomField = in_array($this->orderRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->orderRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('orders.create')->with("customFields", isset($html) ? $html : false)->with("user", $user)->with("driver", $driver)->with("orderStatus", $orderStatus);
    }

    /**
     * Store a newly created Order in storage.
     *
     * @param CreateOrderRequest $request
     *
     * @return Response
     */
    public function store(CreateOrderRequest $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->orderRepository->model());
        try {
            $order = $this->orderRepository->create($input);
            $order->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.order')]));

        return redirect(route('orders.index'));
    }

    /**
     * Display the specified Order.
     *
     * @param int $id
     * @param FoodOrderDataTable $foodOrderDataTable
     *
     * @return Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */

    public function show(FoodOrderDataTable $foodOrderDataTable, $id)
    {
        $this->orderRepository->pushCriteria(new OrdersOfUserCriteria(auth()->id()));
        $order = $this->orderRepository->findWithoutFail($id);
        if (empty($order)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.order')]));

            return redirect(route('orders.index'));
        }
        $subtotal = 0;

        foreach ($order->foodOrders as $foodOrder) {
            foreach ($foodOrder->extras as $extra) {
                $foodOrder->price += $extra->price;
            }
            $subtotal += $foodOrder->price * $foodOrder->quantity;
        }

        $total = $subtotal + $order['delivery_fee'];
        $taxAmount = $total * $order['tax'] / 100;
        $total += $taxAmount;
        $foodOrderDataTable->id = $id;

        return $foodOrderDataTable->render('orders.show', ["order" => $order, "total" => $total, "subtotal" => $subtotal, "taxAmount" => $taxAmount]);
    }

    /**
     * Show the form for editing the specified Order.
     *
     * @param int $id
     *
     * @return Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function edit($id)
    {
        $this->orderRepository->pushCriteria(new OrdersOfUserCriteria(auth()->id()));
        $order = $this->orderRepository->findWithoutFail($id);
        if (empty($order)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.order')]));

            return redirect(route('orders.index'));
        }

        $restaurant = $order->foodOrders()->first();
        $restaurant = empty($restaurant) ? $restaurant->food['restaurant_id'] : 0;

        $user = $this->userRepository->getByCriteria(new ClientsCriteria())->pluck('name', 'id');
        $driver = $this->userRepository->getByCriteria(new DriversOfRestaurantCriteria($restaurant))->pluck('name', 'id');
        $orderStatus = $this->orderStatusRepository->pluck('status', 'id');


        $customFieldsValues = $order->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->orderRepository->model());
        $hasCustomField = in_array($this->orderRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('orders.edit')->with('order', $order)->with("customFields", isset($html) ? $html : false)->with("user", $user)->with("driver", $driver)->with("orderStatus", $orderStatus);
    }

    /*
    * Sorting the Distance Array
    */
    private function sort_array_of_array(&$array, $subfield)
    {
        $sortarray = array();
        foreach ($array as $key => $row) {
            $sortarray[$key] = $row[$subfield];
        }

        array_multisort($sortarray, SORT_ASC, $array);
    }

    /**
     * Update the specified Order in storage.
     *
     * @param int $id
     * @param UpdateOrderRequest $request
     *
     * @return Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function update($id, UpdateOrderRequest $request)
    {
        $restaurant = Order::find($id)->foods[0]->restaurant->first();

        $this->orderRepository->pushCriteria(new OrdersOfUserCriteria(auth()->id()));
        $oldOrder = $this->orderRepository->findWithoutFail($id);
        if (empty($oldOrder)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.order')]));
            return redirect(route('orders.index'));
        }
        $oldStatus = $oldOrder->payment->status;
        // ** OLD WORLK
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->orderRepository->model());
        try {
            // return $input;
            $order = $this->orderRepository->update($input, $id);

            if (setting('enable_notifications', false)) {
                if (isset($input['order_status_id']) && $input['order_status_id'] != $oldOrder->order_status_id) {
                    Notification::send([$order->user], new StatusChangedOrder($order));
                }

                if (isset($input['driver_id']) && ($input['driver_id'] != $oldOrder['driver_id'])) {
                    $driver = $this->userRepository->findWithoutFail($input['driver_id']);
                    if (!empty($driver)) {
                        Notification::send([$driver], new AssignedOrder($order));
                    }
                }
            }

            $payment = $this->paymentRepository->update([
                "status" => $input['status'],
            ], $order['payment_id']);
            //dd($input['status']);

            event(new OrderChangedEvent($oldStatus, $order));

            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $order->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }

            //Add notification by mail when changing order status
            Mail::to($order->user->email)->send(new EmailOrder($order, $this->orderRepository, $payment));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.order')]));
        return redirect(route('orders.index'));
    }

    // !!!  Muzammil Hussain
    /**
     * Assign Order to the nearest driver
     * 
     * @param int $id
     */
    public function assignOrder($id, $status = null)
    {
        ini_set('max_execution_time', 0);
        $startTime = now("Asia/Karachi");

        $driverDistances = array();

        $order = Order::findOrFail($id);


        // return $this->sendError("Notification sent.");

        // ** Check if order needs to be assigned
        if ($order->order_status_id != 2 && !isset($status) || $status != 2) {
            return $this->sendError("Order is not in preparing phase.");
        }
        // ** Check if the driver is already assigned to the order
        if ($order->driver_id != '' || $order->driver_id != null) {
            return $this->sendError("Driver is already assigned to the drriver.");
        }

        $foods = $order->foods;

        // Check if order has any foods
        if (sizeof($foods) == 0) {
            return $this->sendError("The order has no foods");
        }



        $restaurant = Restaurant::find($foods[0]->restaurant->id)->first();

        $restLatitude = $restaurant->latitude;
        $restLongitude = $restaurant->longitude;

        // ** Get Locations
        $acitiveDrivers =  Driver::where('available', '1')->where('is_assigned', '0')->with(['user' => function ($q) {
            $q->with('location');
        }])->get();

        if (sizeof($acitiveDrivers) == 0) {
            $acitiveDrivers = Driver::where('available', '1')->where('is_assigned', '1')->with(['user' => function ($q) {
                $q->with('location');
            }])->get();
        }

        // If there are no active drivers
        if (sizeof($acitiveDrivers) == 0) {
            return $this->sendError("There are no active drivers at the moment.");
        }

        // ** Create Array of locations and driver id
        foreach ($acitiveDrivers as $driver) {
            if ($driver->user->location != null) {
                $lat1 = $driver->user->location->latitude;
                $lon1 = $driver->user->location->longitude;

                $distanceBetween = $this->distance($lat1, $lon1, $restLatitude, $restLongitude);

                array_push($driverDistances, ['driver_id' => $driver->user->id, 'distance' => $distanceBetween]);
            }
        }

        // ** Sort the array on the basis of distance
        $this->sort_array_of_array($driverDistances, 'distance');

        // ** Send Notification To each driver

        $isAssigned = false;
        foreach ($driverDistances as $driverDistance) {
            $pendingOrder = PendingOrder::create(['order_id' => $id, 'driver_id' => $driverDistance['driver_id']]);

            Notification::send(User::where('id', $driverDistance['driver_id'])->first(), new AcceptOrRejectOrder($order));

            for ($i = 1; $i <= 2; $i++) {


                sleep(30);
                $isAccepted = PendingOrder::find($pendingOrder->id);
                if ($isAccepted->is_accepted == 1) {
                    $driver = $this->userRepository->findWithoutFail($isAccepted->driver_id);


                    if (!empty($driver)) {
                        // Actually assign the order
                        if ($order->delivered_by == 'multi_rider') {
                            $order->driver_id = $driver->id;
                            $pendingOrder->driver_id = $driver->id;
                            $pendingOrder->is_accepted = 1;
                            $order->save();
                            $pendingOrder->save();
                            $isAssigned = true;

                            PendingOrder::where('order_id', $order->id)->delete();
                            break;
                        } else if ($order->delivered_by == 'single_rider') {
                            Order::where('order_group_id', $order->order_group_id)->update([
                                'driver_id' => $driver->id,
                            ]);
                            $isAssigned = true;
                            PendingOrder::where('order_id', $order->id)->delete();
                            break;
                        }
                    }
                } else if ($isAccepted->is_rejected == 0) {
                    PendingOrder::where('order_id', $order->id)->delete();
                    break;
                }
            }
            if ($isAssigned) {
                break;
            } else {
                $pendingOrder->is_rejected = 1;
                $pendingOrder->save();
            }
        }

        // If driver is still not assigned to the order, Assign it anyway to the first nearest rider.
        $driver = $this->userRepository->findWithoutFail($driverDistances[0]["driver_id"]);
        if ($isAssigned == false && $order->delivered_by == "multi_rider") {
            $order->driver_id = $driver->id;
            $order->save();
        } else if ($isAssigned == false && $order->delivered_by == "single_rider") {
            Order::where('order_group_id', $order->order_group_id)->update([
                'driver_id' => $driver->id,
            ]);
        }


        Notification::send(User::where('id', $order->driver_id)->first(), new AssignedOrder($order));

        return Response(['success' => true, 'message' => "The order is assigend to a driver."]);
    }

    /**
     * Remove the specified Order from storage.
     *
     * @param int $id
     *
     * @return Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function destroy($id)
    {
        if (!env('APP_DEMO', false)) {
            $this->orderRepository->pushCriteria(new OrdersOfUserCriteria(auth()->id()));
            $order = $this->orderRepository->findWithoutFail($id);

            if (empty($order)) {
                Flash::error(__('lang.not_found', ['operator' => __('lang.order')]));

                return redirect(route('orders.index'));
            }

            $this->orderRepository->delete($id);

            Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.order')]));
        } else {
            Flash::warning('This is only demo app you can\'t change this section ');
        }
        return redirect(route('orders.index'));
    }

    /**
     * Remove Media of Order
     * @param Request $request
     */
    public function removeMedia(Request $request)
    {
        $input = $request->all();
        $order = $this->orderRepository->findWithoutFail($input['id']);
        try {
            if ($order->hasMedia($input['collection'])) {
                $order->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }




    function distance($lat1, $lon1, $lat2, $lon2, $unit = "k")
    {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}
