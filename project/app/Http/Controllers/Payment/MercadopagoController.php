<?php

namespace App\Http\Controllers\Payment;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shipping;
use App\Models\Package;

class MercadopagoController extends Controller
{

    private $access_token;

    public function __construct()
    {
        //Set Spripe Keys
        $gs = Generalsetting::findOrFail(1);
        $this->access_token = $gs->mercado_token;

    }

    public function store(Request $request) {

     if(!$request->has('order_number')){
         return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
     }
    
     $order_number = $request->order_number;
            $order = Order::where('order_number',$order_number)->firstOrFail();
             $curr = Currency::where('sign','=',$order->currency_sign)->firstOrFail();
           
            $input = $request->all();
            $shipping = Shipping::findOrFail($request->shipping)->price * $order->currency_value;
            $packeging = Package::findOrFail($request->packeging)->price * $order->currency_value;
            
            $charge = $shipping + $packeging;
            $settings = Generalsetting::findOrFail(1);
           
            $item_amount = $order->pay_amount * $order->currency_value;
            $item_amount += $charge;
            $item_name = $settings->title." Order";

                $available_currency = array(
                    'ARS',
                    'BRL',
                    'CLP',
                    'MXN',
                    'PEN',
                    'UYU',
                    'VEF'
                    );
                    if(!in_array($curr->name,$available_currency))
                    {
                    return redirect()->back()->with('unsuccess','Invalid Currency For Mercadopago.');
                    }

        
         $settings = Generalsetting::findOrFail(1);
        $order->packing_cost = $packeging;
        $order->shipping_cost = $shipping;
        $order->pay_amount = $item_amount / $curr->value;
        $order['method'] = $request->method;
        $order->update();
         
         $cancel_url = action('Front\PaymentController@paycancle');
         $notify_url = action('Payment\MercadopagoController@notify');
        $return_url = route('front.payment.success',1);
            
        
            $curl = curl_init();

            $preferenceData = [
                'items' => [
                    [
                        'id' => $order_number,
                        'title' => $item_name,
                        'description' =>$item_name,
                        'quantity' => 1,
                        'currency_id' => $curr->name,
                        'unit_price' => $item_amount
                    ]
                ],
                'payer' => [
                    'email' => $request->email,
                ],
                'back_urls' => [
                    'success' => $return_url,
                    'pending' => '',
                    'failure' => $cancel_url,
                ],
                'notification_url' =>  $notify_url,
                'auto_return' =>  'approved',
    
            ];
    
            $httpHeader = [
                "Content-Type: application/json",
            ];
            $url = "https://api.mercadopago.com/checkout/preferences?access_token=".$this->access_token;
            $opts = [
                CURLOPT_URL             => $url,
                CURLOPT_CUSTOMREQUEST   => "POST",
                CURLOPT_POSTFIELDS      => json_encode($preferenceData,true),
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_TIMEOUT         => 30,
                CURLOPT_HTTPHEADER      => $httpHeader
            ];
    
            curl_setopt_array($curl, $opts);
    
            $response = curl_exec($curl);
            $err = curl_error($curl);
    
            curl_close($curl);
            $payment = json_decode($response,true);
              

         if($settings->mercadopago_sandbox_check == 1)
         {
             return redirect($payment['sandbox_init_point']);
         }
         else {
             return redirect($payment['init_point']);
         }
    }

    public function curlCalls($url){
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        $paymentData = curl_exec($ch);
        curl_close($ch);
        return $paymentData;
    }

 


    public function notify(Request $request)
    {
        
    
        $paymentUrl = "https://api.mercadopago.com/v1/payments/".$request['data']['id']."?access_token=".$this->access_token;

        $paymentData = $this->curlCalls($paymentUrl);

        $payment = json_decode($paymentData,true);

        $merchantUrl = "https://api.mercadopago.com/merchant_orders/".$payment['order']['id']."?access_token=".$this->access_token;

        $merchantData = $this->curlCalls($merchantUrl);

        $merchant = json_decode($merchantData,true);


        $order_number = $merchant['items'][0]['id'];

            if ($payment['status'] == 'approved'){
                $order = Order::where('order_number', $order_number)->first();
                $data['payment_status'] = "Completed";
                $data['txnid'] = $request['data']['id'];
                if($order->dp == 1)
                {
                    $data['status'] = 'completed';
                }
                $order->update($data);
            }

    }

    // Capcha Code Image
    private function  code_image()
    {
        $actual_path = str_replace('project','',base_path());
        $image = imagecreatetruecolor(200, 50);
        $background_color = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image,0,0,200,50,$background_color);

        $pixel = imagecolorallocate($image, 0,0,255);
        for($i=0;$i<500;$i++)
        {
            imagesetpixel($image,rand()%200,rand()%50,$pixel);
        }

        $font = $actual_path.'assets/front/fonts/NotoSans-Bold.ttf';
        $allowed_letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $length = strlen($allowed_letters);
        $letter = $allowed_letters[rand(0, $length-1)];
        $word='';
        //$text_color = imagecolorallocate($image, 8, 186, 239);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        $cap_length=6;// No. of character in image
        for ($i = 0; $i< $cap_length;$i++)
        {
            $letter = $allowed_letters[rand(0, $length-1)];
            imagettftext($image, 25, 1, 35+($i*25), 35, $text_color, $font, $letter);
            $word.=$letter;
        }
        $pixels = imagecolorallocate($image, 8, 186, 239);
        for($i=0;$i<500;$i++)
        {
            imagesetpixel($image,rand()%200,rand()%50,$pixels);
        }
        session(['captcha_string' => $word]);
        imagepng($image, $actual_path."assets/images/capcha_code.png");
    }

}