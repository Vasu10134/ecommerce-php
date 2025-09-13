<?php

namespace App\Http\Controllers\User\Payment;

use App\Models\Deposit;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaytmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->view('front.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->has('deposit_number')) {
            return response()->json([
                'status' => false,
                'data' => [],
                'error' => 'Invalid Request'
            ]);
        }

        $deposit_number = $request->deposit_number;
        $order = Deposit::where('deposit_number', $deposit_number)->firstOrFail();
        $curr = Currency::where('name', $order->currency_code)->firstOrFail();

        if ($curr->name != "INR") {
            return redirect()->back()->with('unsuccess', 'Please Select INR Currency For Paytm.');
        }

        $item_amount = $order->amount * $curr->value;
        $data_for_request = $this->handlePaytmRequest($order->deposit_number, $item_amount);

        $paytm_txn_url = 'https://securegw-stage.paytm.in/theia/processTransaction';
        $paramList = $data_for_request['paramList'];
        $checkSum = $data_for_request['checkSum'];

        return response()->view('front.paytm-merchant-form', compact('paytm_txn_url', 'paramList', 'checkSum'));
    }

    /**
     * Handle Paytm request and generate checksum.
     */
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
            "CALLBACK_URL" => route('api.user.paytm.notify')
        ];

        $checkSum = getChecksumFromArray($paramList, $gs->paytm_secret);

        return [
            'checkSum' => $checkSum,
            'paramList' => $paramList
        ];
    }

    /**
     * Load encryption/decryption helper functions.
     */
    private function getAllEncdecFunc(): void
    {
        if (!function_exists('encrypt_e')) {
            function encrypt_e($input, $ky) {
                $key = html_entity_decode($ky);
                $iv = "@@@@&&&&####$$$$";
                return openssl_encrypt($input, "AES-128-CBC", $key, 0, $iv);
            }
        }

        if (!function_exists('decrypt_e')) {
            function decrypt_e($crypt, $ky) {
                $key = html_entity_decode($ky);
                $iv = "@@@@&&&&####$$$$";
                return openssl_decrypt($crypt, "AES-128-CBC", $key, 0, $iv);
            }
        }

        if (!function_exists('generateSalt_e')) {
            function generateSalt_e($length) {
                $data = "AbcDE123IJKLMN67QRSTUVWXYZaBCdefghijklmn123opq45rs67tuv89wxyz0FGH45OP89";
                $random = '';
                for ($i = 0; $i < $length; $i++) {
                    $random .= substr($data, rand(0, strlen($data) - 1), 1);
                }
                return $random;
            }
        }

        if (!function_exists('checkString_e')) {
            function checkString_e($value) {
                return $value === 'null' ? '' : $value;
            }
        }

        if (!function_exists('getChecksumFromArray')) {
            function getChecksumFromArray($arrayList, $key, $sort = 1) {
                if ($sort != 0) ksort($arrayList);
                $str = getArray2Str($arrayList);
                $salt = generateSalt_e(4);
                $finalString = $str . "|" . $salt;
                $hash = hash("sha256", $finalString);
                return encrypt_e($hash . $salt, $key);
            }
        }

        if (!function_exists('getArray2Str')) {
            function getArray2Str($arrayList) {
                $paramStr = "";
                $flag = true;
                foreach ($arrayList as $value) {
                    if ($flag) {
                        $paramStr .= checkString_e($value);
                        $flag = false;
                    } else {
                        $paramStr .= "|" . checkString_e($value);
                    }
                }
                return $paramStr;
            }
        }
    }

    /**
     * Paytm callback.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function paytmCallback(Request $request)
    {
        $order_id = $request->input('ORDERID');
        $order = Deposit::where('deposit_number', $order_id)->first();
        $cancel_url = route('user.deposit.send', $order->deposit_number);

        if ($request->input('STATUS') === 'TXN_SUCCESS') {
            $transaction_id = $request->input('TXNID');

            if ($order) {
                $order->update([
                    'txnid' => $transaction_id,
                    'payment_status' => 'Completed',
                    'method' => 'Paytm'
                ]);
            }

            return redirect()->route('user.success', 1);
        }

        return redirect($cancel_url);
    }
}
