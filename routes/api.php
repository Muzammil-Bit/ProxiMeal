<?php

//Vehicles
Route::resource('vehicles', 'API\VehicleAPIController');


Route::prefix('driver')->group(function () {
    Route::post('login', 'API\Driver\UserAPIController@login');
    Route::post('register', 'API\Driver\UserAPIController@register');
    Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
    Route::get('user', 'API\Driver\UserAPIController@user');
    Route::get('logout', 'API\Driver\UserAPIController@logout');
    Route::get('settings', 'API\Driver\UserAPIController@settings');
    Route::post('/status/{status}', 'API\Driver\UserAPIController@updateStatus');

    // ** Toggle Driver Status
    Route::get('status/{id}', 'API\DriverStatusController@update');
    //** ACCEPT ORDER BY DRIVER */
    Route::post('orders/accept/{id}', 'API\PendingOrderController@accept')->middleware('auth:api');
    Route::post('orders/reject/{id}', 'API\PendingOrderController@reject');
    Route::post('orders/notifications/{id}', 'API\PendingOrderController@notifications');
});

Route::post('uploads/store', 'UploadController@storeAPI')->name('medias.create');

Route::prefix('manager')->group(function () {
    Route::post('login', 'API\Manager\UserAPIController@login');
    Route::post('register', 'API\Manager\UserAPIController@register');
    Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
    Route::get('user', 'API\Manager\UserAPIController@user');
    Route::get('logout', 'API\Manager\UserAPIController@logout');
    Route::get('settings', 'API\Manager\UserAPIController@settings');
});

// ADDED BY Muzammil Hussain
Route::post('location/update', "API\LocationController@store");
Route::post('location/get', "API\LocationController@show");



// ** Cost Caculator
Route::post('orders/cost/{id}', 'API\DeliveryCostController@index');

// **Assign Order
Route::post('orders/assign/{id}/{status?}', 'OrderController@assignOrder')->name('orders.assign');

// *************************

Route::post('login', 'API\UserAPIController@login');
Route::post('register', 'API\UserAPIController@register');
Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
Route::get('user', 'API\UserAPIController@user');
Route::get('logout', 'API\UserAPIController@logout');
Route::get('settings', 'API\UserAPIController@settings');

Route::resource('cuisines', 'API\CuisineAPIController');
Route::resource('categories', 'API\CategoryAPIController');
Route::resource('restaurants', 'API\RestaurantAPIController');

Route::resource('faq_categories', 'API\FaqCategoryAPIController');
Route::get('foods/categories', 'API\FoodAPIController@categories');
Route::resource('foods', 'API\FoodAPIController');
Route::resource('galleries', 'API\GalleryAPIController');
Route::resource('food_reviews', 'API\FoodReviewAPIController');
Route::resource('nutrition', 'API\NutritionAPIController');
Route::resource('extras', 'API\ExtraAPIController');
Route::resource('extra_groups', 'API\ExtraGroupAPIController');
Route::resource('faqs', 'API\FaqAPIController');
Route::resource('restaurant_reviews', 'API\RestaurantReviewAPIController');
Route::resource('currencies', 'API\CurrencyAPIController');
Route::resource('slides', 'API\SlideAPIController')->except([
    'show'
]);

Route::middleware('auth:api')->group(function () {
    Route::group(['middleware' => ['role:driver']], function () {
        Route::prefix('driver')->group(function () {
            Route::resource('orders', 'API\OrderAPIController');
            Route::resource('notifications', 'API\NotificationAPIController');
            Route::post('users/{id}', 'API\UserAPIController@update');
            Route::resource('faq_categories', 'API\FaqCategoryAPIController');
            Route::resource('faqs', 'API\FaqAPIController');
        });
    });
    Route::group(['middleware' => ['role:manager']], function () {
        Route::prefix('manager')->group(function () {
            Route::post('users/{id}', 'API\UserAPIController@update');
            Route::get('users/drivers_of_restaurant/{id}', 'API\Manager\UserAPIController@driversOfRestaurant');
            Route::get('dashboard/{id}', 'API\DashboardAPIController@manager');
            Route::resource('restaurants', 'API\Manager\RestaurantAPIController');
            Route::get('restaurant/foods', 'API\Manager\RestaurantAPIController@getFoods');
            Route::resource('faq_categories', 'API\FaqCategoryAPIController');
            Route::resource('faqs', 'API\FaqAPIController');
            Route::get('extras', 'API\Manager\RestaurantAPIController@getExtras');

            Route::get('earnings', 'API\Manager\EarningAPIController@index');
        });
    });
    Route::post('users/{id}', 'API\UserAPIController@update');

    Route::resource('order_statuses', 'API\OrderStatusAPIController');

    Route::get('payments/byMonth', 'API\PaymentAPIController@byMonth')->name('payments.byMonth');
    Route::resource('payments', 'API\PaymentAPIController');

    Route::get('favorites/exist', 'API\FavoriteAPIController@exist');
    Route::resource('favorites', 'API\FavoriteAPIController');

    Route::resource('orders', 'API\OrderAPIController');

    Route::resource('food_orders', 'API\FoodOrderAPIController');

    Route::resource('notifications', 'API\NotificationAPIController');

    Route::get('carts/count', 'API\CartAPIController@count')->name('carts.count');
    Route::resource('carts', 'API\CartAPIController');

    Route::resource('delivery_addresses', 'API\DeliveryAddressAPIController');

    Route::resource('drivers', 'API\DriverAPIController');

    Route::resource('earnings', 'API\EarningAPIController');

    Route::resource('driversPayouts', 'API\DriversPayoutAPIController');

    Route::resource('restaurantsPayouts', 'API\RestaurantsPayoutAPIController');

    Route::resource('coupons', 'API\CouponAPIController')->except([
        'show'
    ]);
});
