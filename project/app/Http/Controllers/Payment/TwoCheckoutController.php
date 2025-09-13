<?php

namespace App\Http\Controllers\Payment;


use Twocheckout;
use Twocheckout_Charge;
use Twocheckout_Error;
use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderTrack;
use App\Models\Pagesetting;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\VendorOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shipping;
use App\Models\Package;
use Illuminate\Support\Facades\Session;
class TwoCheckoutController extends Controller
{

public function store(Request $request){

   
   
  

    $settings = Generalsetting::findOrFail(1);

        $order_number = $request->order_number;
        $order = Order::where('order_number',$order_number)->firstOrFail();
        $curr = Currency::where('sign','=',$order->currency_sign)->firstOrFail();
        
        $input = $request->all();
        $shipping = Shipping::findOrFail($request->shipping)->price * $order->currency_value;
        $packeging = Package::findOrFail($request->packeging)->price * $order->currency_value;
        
        $charge = $shipping + $packeging;
        $settings = Generalsetting::findOrFail(1);
       
        $item_amount = $order->pay_amount ;
        $item_amount += $charge;
        
        
    
    Twocheckout::privateKey($settings->twocheckout_private_key);
    Twocheckout::sellerId($settings->twocheckout_seller_id);
    
    
    if($settings->twocheckout_sandbox_check == 1) {
        Twocheckout::sandbox(true);
    }
    else {
        Twocheckout::sandbox(false);
    }

     
        try {

            $charge = Twocheckout_Charge::auth(array(
                "merchantOrderId" => $order_number,
                "token"      => $request->_token,
                "currency"   => $curr->name,
                "total"      => $item_amount,
                "billingAddr" => array(
                    "name" => $order->customer_name,
                    "addrLine1" => $order->customer_address,
                    "city" => $order->customer_city,
                    "state" => $order->customer_state,
                    "zipCode" => $order->customer_zip,
                    "country" => $order->customer_country,
                    "email" => $request->email,
                    "phoneNumber" => $order->customer_phone
                )
            ));
        
            if ($charge['response']['responseCode'] == 'APPROVED') {
    
                
            
            $order['pay_amount'] = round($item_amount / $curr->value, 2);
            $order['method'] = "2Checkout";
            $order['payment_status'] = "Completed";
            $order['txnid'] = $charge['response']['transactionId'];
           
            $order->update();
           
           return redirect(route('front.payment.success',1));
        
            }
    
        } catch (Twocheckout_Error $e) {
            return redirect()->back()->with('unsuccess',$e->getMessage());
    
        }

}



}
