<?php

namespace App\Http\Controllers\Payment;

use App\Models\Cart;
use App\Models\User;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Currency;
use App\Models\OrderTrack;
use App\Models\VendorOrder;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Classes\GeniusMailer;
use App\Models\Generalsetting;
use App\Models\UserNotification;
use App\Http\Controllers\Controller;
use App\Models\Pagesetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Shipping;
use App\Models\Package;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PaytmController extends Controller
{
    // Display a listing of the resource
    public function index(): View
    {
        return view('front.index');
    }

    // Show the form for creating a new resource
    public function create(): View
    {
        return view('front.create');
    }

    // Store a newly created resource in storage
    public function store(Request $request): Response|JsonResponse|RedirectResponse|View
    {
        if (!$request->has('order_number')) {
            return response()->json([
                'status' => false,
                'data' => [],
                'error' => 'Invalid Request'
            ]);
        }

        $order_number = $request->order_number;
        $order = Order::where('order_number', $order_number)->firstOrFail();
        $curr = Currency::where('sign', $order->currency_sign)->firstOrFail();

        if ($curr->name != "INR") {
            return redirect()->back()->with('unsuccess', 'Please Select INR Currency For Paytm.');
        }

        $shipping = Shipping::findOrFail($request->shipping)->price * $order->currency_value;
        $packaging = Package::findOrFail($request->packeging)->price * $order->currency_value;
        $charge = $shipping + $packaging;

        $item_amount = $order->pay_amount * $order->currency_value + $charge;

        $data_for_request = $this->handlePaytmRequest($order->order_number, $item_amount);
        $paytm_txn_url = 'https://securegw-stage.paytm.in/theia/processTransaction';
        $paramList = $data_for_request['paramList'];
        $checkSum = $data_for_request['checkSum'];

        return view('front.paytm-merchant-form', compact('paytm_txn_url', 'paramList', 'checkSum'));
    }

    // Generate Paytm request data
    public function handlePaytmRequest(string $order_id, float $amount): array
    {
        $gs = Generalsetting::first();

        $this->getAllEncdecFunc();

        $paramList = [
            "MID" => $gs->paytm_merchant,
            "ORDER_ID" => $order_id,
            "CUST_ID" => $order_id,
            "INDUSTRY_TYPE_ID" => $gs->paytm_industry,
            "CHANNEL_ID" => 'WEB',
            "TXN_AMOUNT" => $amount,
            "WEBSITE" => $gs->paytm_website,
            "CALLBACK_URL" => route('api.paytm.notify'),
        ];

        $checkSum = $this->getChecksumFromArray($paramList, $gs->paytm_secret);

        return [
            'checkSum' => $checkSum,
            'paramList' => $paramList
        ];
    }

    // Encdec & checksum functions
    private function getAllEncdecFunc(): void
    {
        // All helper functions moved as private methods
    }

    private function encrypt_e(string $input, string $key): string
    {
        $iv = "@@@@&&&&####$$$$";
        return openssl_encrypt($input, "AES-128-CBC", html_entity_decode($key), 0, $iv);
    }

    private function decrypt_e(string $crypt, string $key): string
    {
        $iv = "@@@@&&&&####$$$$";
        return openssl_decrypt($crypt, "AES-128-CBC", html_entity_decode($key), 0, $iv);
    }

    private function pkcs5_unpad_e(string $text): string|false
    {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text)) return false;
        return substr($text, 0, -1 * $pad);
    }

    private function generateSalt_e(int $length): string
    {
        $random = '';
        $data = "AbcDE123IJKLMN67QRSTUVWXYZaBCdefghijklmn123opq45rs67tuv89wxyz0FGH45OP89";
        for ($i = 0; $i < $length; $i++) {
            $random .= $data[rand(0, strlen($data) - 1)];
        }
        return $random;
    }

    private function checkString_e(string $value): string
    {
        return ($value === 'null') ? '' : $value;
    }

    private function getChecksumFromArray(array $arrayList, string $key): string
    {
        ksort($arrayList);
        $str = $this->getArray2Str($arrayList);
        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hashString = hash("sha256", $finalString) . $salt;
        return $this->encrypt_e($hashString, $key);
    }

    private function getArray2Str(array $arrayList): string
    {
        $paramStr = '';
        $flag = true;
        foreach ($arrayList as $value) {
            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = false;
            } else {
                $paramStr .= '|' . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    private function callRefundAPI(string $refundApiURL, array $requestParamList): array
    {
        $jsonData = json_encode($requestParamList);
        $postData = 'JsonData=' . urlencode($jsonData);

        $ch = curl_init($refundApiURL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $refundApiURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ]);

        $jsonResponse = curl_exec($ch);
        return json_decode($jsonResponse, true) ?? [];
    }

    // Paytm callback handler
    public function paytmCallback(Request $request): RedirectResponse
    {
        $order_id = $request['ORDERID'];
        $order = Order::where('order_number', $order_id)->first();
        $cancel_url = route('payment.checkout') . "?order_number=" . $order->order_number;

        if ($request['STATUS'] === 'TXN_SUCCESS') {
            if ($order) {
                $order->update([
                    'txnid' => $request['TXNID'],
                    'payment_status' => 'Completed',
                    'method' => 'Paytm'
                ]);
            }
            return redirect(route('front.payment.success', 1));
        }

        return redirect($cancel_url);
    }
}
