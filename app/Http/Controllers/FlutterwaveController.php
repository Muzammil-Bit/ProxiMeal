<?php
/**
 * File name: PayPalController.php
 * Last modified: 2020.06.11 at 16:10:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 */

namespace App\Http\Controllers;

use App\Models\Payment;
use Flash;
use Illuminate\Http\Request;
      
class FlutterwaveController extends ParentOrderController
{
    /**
     * @var ExpressCheckout
     */
    protected $provider;

    public function __init()
    {
        // $this->provider = new ExpressCheckout();
    }

    public function index()
    {
        return view('welcome');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getExpressCheckout(Request $request)
    {
        $user = $this->userRepository
            ->findByField('api_token', $request->get('api_token'))
            ->first();
        $coupon = $this->couponRepository
            ->findByField('code', $request->get('coupon_code'))
            ->first();
        $deliveryId = $request->get('delivery_address_id');
        if (!empty($user)) {
            $this->order->user = $user;
            $this->order->user_id = $user->id;
            $this->order->delivery_address_id = $deliveryId;
            $this->coupon = $coupon;
            $flutterCart = $this->getCheckoutData();
            try {
                // $response = $this->provider->setExpressCheckout($payPalCart);
                if (!empty($flutterCart)) {
                    return view('payments.flutter_checkout')
                        ->with('data', $flutterCart)
                        ->render();
                } else {
                    Flash::error($response['L_LONGMESSAGE0']);
                }
            } catch (\Exception $e) {
                Flash::error(
                    'Error processing Flutter payment for your order :' .
                        $e->getMessage()
                );
            }
        }
        return redirect(route('payments.failed'));
    }

    /**
     * Set cart data for processing payment on PayPal.
     *
     *
     * @return array
     */
    private function getCheckoutData()
    {
        $data = [];
        $this->calculateTotal();
        $order_id = $this->paymentRepository->all()->count() + 1;
        $data['items'][] = [
            'name' => $this->order->user->cart[0]->food->restaurant->name,
            'price' => $this->total,
            'qty' => 1,
        ];
        $data['total'] = $this->total;
        $data['return_url'] = url(
            'payments/flutter/express-checkout-success?user_id=' .
                $this->order->user_id .
                '&delivery_address_id=' .
                $this->order->delivery_address_id
        );

        if (isset($this->coupon)) {
            $data['return_url'] .= '&coupon_code=' . $this->coupon->code;
        }
        $user = $this->userRepository->find($this->order->user_id);

        $data['ref'] = 'ref' . $order_id;
        $data['user_id'] = $this->order->user_id;
        if ($user !== null) {
            $data['user_email'] = $user->email;
            $data['user_name'] = $user->name;
        }
        $data['public_key'] = 'FLWPUBK_TEST-5bd71229e88c71eef0ada3f38104ac34-X';
        $data['pay_method'] = config('services.flutter.paymethod');
        $data['title'] = config('services.flutter.title');
        $data['description'] = config('services.flutter.description');
        $data['currency'] = config('services.flutter.currency');
        $data['country'] = config('services.flutter.key');
        $data['logo'] = config('services.flutter.logo');
        $data['cancel_url'] = url('payments/flutter');
        $data['invoice_id'] = $order_id . '_' . date('Y_m_d_h_i_sa');
        $data['invoice_description'] =
        $this->order->user->cart[0]->food->restaurant->name;

        //dd($data);
        return $data;
    }

    /**
     * Process payment on PayPal.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function getExpressCheckoutSuccess(Request $request)
    {
        $this->order->user_id = $request->get('user_id', 0);
        $user = $this->userRepository->findWithoutFail($this->order->user_id);
        $this->order->user = $user;
        $this->coupon = $this->couponRepository
            ->findByField('code', $request->get('coupon_code'))
            ->first();
        $this->order->delivery_address_id = $request->get(
            'delivery_address_id',
            0
        );

        // Verify Express Checkout Token
        // $flutterCart = $this->getCheckoutData();
        $curl = curl_init();
        $transaction_id = $request->get('transaction_id');
        $private_key = config('services.flutter.key');
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $private_key,
            ],
        ]);

        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);

        if (in_array(strtoupper($response['status']), ['SUCCESS'])) {
            // Perform transaction on PayPal

            $this->order->payment = new Payment();
            $this->order->payment->status = 'successful';
            $this->order->payment->method = 'flutter';
            $this->createOrder();
            Flash::message('Success');
            return redirect(url('payments/flutter'));
        } else {
            Flash::error('Error processing flutter payment for your order');
            return redirect(route('payments.failed'));
        }
    }
}