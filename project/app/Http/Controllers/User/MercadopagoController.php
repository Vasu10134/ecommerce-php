<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\GeniusMailer;
use App\Models\Generalsetting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;
use Auth;
use Carbon\Carbon;
use Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use App\Models\Currency;
use MercadoPago;

class MercadopagoController extends Controller
{

    private $access_token;

    public function __construct()
    {
        $gs = Generalsetting::findOrFail(1);
        $this->access_token = $gs->mercado_token;

    }

    public function store(Request $request) {

        $user = Auth::user();
        $package = $user->subscribes()->where('status',1)->orderBy('id','desc')->first();
        $subs = Subscription::findOrFail($request->subs_id);
        $settings = Generalsetting::findOrFail(1);
        $today = Carbon::now()->format('Y-m-d');

        if (Session::has('currency'))
        {
            $curr = Currency::find(Session::get('currency'));
        }
        else
        {
            $curr = Currency::where('is_default','=',1)->first();
        }

    $available_currency = array(
        'ARS',
        'BRL',
        'CLP',
        'MXN',
        'PEN',
        'UYU',
        'VEF',
        'USD'
        );

        if(!in_array($curr->name,$available_currency))
        {
            return redirect()->back()->with('unsuccess','Invalid Currency For Mercadopago.');
        }

         $settings = Generalsetting::findOrFail(1);
         $return_url = action('User\MercadopagoController@payreturn');
         $cancel_url = action('User\MercadopagoController@paycancle');
         $notify_url = action('User\MercadopagoController@notify');
    
      
         $item_amount = (double)$subs->price;

         MercadoPago\SDK::setAccessToken($settings->mercado_token);
         $payment = new MercadoPago\Payment();
         $payment->transaction_amount = round($item_amount * $curr->value,2);
         $payment->token = $request->token;
         $payment->description = 'Subscription';
         $payment->installments = 1;

         $payment->payer = array(
            "email" => Auth::user()->email,
        );  
            
        $payment->save();

        if ($payment->status == 'approved') {
            $today = Carbon::now()->format('Y-m-d');
            $date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
            $input = $request->all();  
            $user->is_vendor = 2;
            if(!empty($package))
            {
                if($package->subscription_id == $request->subs_id)
                {
                    $newday = strtotime($today);
                    $lastday = strtotime($user->date);
                    $secs = $lastday-$newday;
                    $days = $secs / 86400;
                    $total = $days+$subs->days;
                    $user->date = date('Y-m-d', strtotime($today.' + '.$total.' days'));
                }
                else
                {
                    $user->date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
                }
            }
            else
            {
                $user->date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
            }
            $user->mail_sent = 1;     
            $user->update($input);
            $sub = new UserSubscription;
            $sub->user_id = $user->id;
            $sub->subscription_id = $subs->id;
            $sub->title = $subs->title;
            $sub->currency = $subs->currency;
            $sub->currency_code = $subs->currency_code;
            $sub->price = $subs->price;
            $sub->days = $subs->days;
            $sub->allowed_products = $subs->allowed_products;
            $sub->details = $subs->details;
            $sub->method = 'Mercadopago';
            $sub->txnid = $payment->id;
            $sub->status = 1;
            $sub->save();

            if($settings->is_smtp == 1)
            {
            $data = [
                'to' => $user->email,
                'type' => "vendor_accept",
                'cname' => $user->name,
                'oamount' => "",
                'aname' => "",
                'aemail' => "",
                'onumber' => "",
            ];
            $mailer = new GeniusMailer();
            $mailer->sendAutoMail($data);        
            }
            else
            {
                $headers = "From: ".$settings->from_name."<".$settings->from_email.">";
                mail($user->email,'Your Vendor Account Activated','Your Vendor Account Activated Successfully. Please Login to your account and build your own shop.',$headers);
            }
            return redirect($return_url);
        }
        else{
            return redirect($cancel_url);
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

    public function paycancle(){
        $this->code_image();
         return redirect()->back()->with('unsuccess','Payment Cancelled.');
     }

     public function payreturn(){
        $this->code_image();
        return redirect()->route('user-dashboard')->with('success','Vendor Account Activated Successfully');
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

            $order = UserSubscription::where('flutter_id','=',$order_number)->orderBy('created_at','desc')->first();
            $user = User::findOrFail($order->user_id);
            $package = $user->subscribes()->where('status',1)->orderBy('id','desc')->first();
            $subs = Subscription::findOrFail($order->subscription_id);
            $settings = Generalsetting::findOrFail(1);
    
            $today = Carbon::now()->format('Y-m-d');
            $date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
            $input = $request->all();
            $user->is_vendor = 2;
            if(!empty($package))
            {
                if($package->subscription_id == $request->subs_id)
                {
                    $newday = strtotime($today);
                    $lastday = strtotime($user->date);
                    $secs = $lastday-$newday;
                    $days = $secs / 86400;
                    $total = $days+$subs->days;
                    $user->date = date('Y-m-d', strtotime($today.' + '.$total.' days'));
                }
                else
                {
                    $user->date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
                }
            }
            else
            {
                $user->date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
            }
            $user->mail_sent = 1;
            $user->update($input);
    
    
            $data['txnid'] = $request['data']['id'];
            $data['status'] = 1;
            $order->update($data);
    
            if($settings->is_smtp == 1)
            {
                $maildata = [
                    'to' => $user->email,
                    'type' => "vendor_accept",
                    'cname' => $user->name,
                    'oamount' => "",
                    'aname' => "",
                    'aemail' => "",
                    'onumber' => "",
                ];
                $mailer = new GeniusMailer();
                $mailer->sendAutoMail($maildata);
            }
            else
            {
                $headers = "From: ".$settings->from_name."<".$settings->from_email.">";
                mail($user->email,'Your Vendor Account Activated','Your Vendor Account Activated Successfully. Please Login to your account and build your own shop.',$headers);
            }
            }
            else{
                $payment = UserSubscription::where('flutter_id','=',$order_number)
                    ->orderBy('created_at','desc')->first();
                $payment->delete();
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
