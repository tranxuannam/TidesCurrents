<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use App\Models\User;
use App\Models\Packages;
use App\Models\Payment;
use App\Payment\iTunes\AbstractResponse;
use App\Payment\iTunes\Validator as iTunesValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseController extends BaseController
{
    protected const HTTP_BAD_REQUEST = 404;
    protected const MINUTE_PACKAGE_TYPE = 'minute';

    /**
     * Store a receipt
     *
     * @param  [string] receipt data
     * @param  [string] package type
     * @param  [string] request type
     * @return [json] payment object
     */
    public function verifyPurchase(Request $request)
    {
        $input = $request->all();
        $rules = [
            'receipt' => 'required|string',
            'package_type' => 'required|in:' . Packages::TYPE_PACKAGE_MONTH . ',' . Packages::TYPE_PACKAGE_MINUTE . ',' . Packages::TYPE_PACKAGE_MONTH_RENEW . ',' . Packages::TYPE_PACKAGE_AUTO_RENEWAL . ',' . Packages::TYPE_PACKAGE_AUTO_PAYMENT,
        ];
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return $this->error($validator->errors(), self::HTTP_BAD_REQUEST, $input);
        }        

        $package_type = $input['package_type'];
        $requestType = ($input['is_restore'] && $input['is_restore'] == 'true')
            ? Packages::REQUEST_TYPE_RESTORE
            : ($input['request_type'] ? $input['request_type'] : '');
        
        $user_id = $this->_getId();
        $user = User::find($user_id);
        if (!$user) {
            return $this->error($this->errors(), self::HTTP_BAD_REQUEST, null);
        }
        
        Log::info('=========================START LOG FOR USER: ' . $user_id . '=============================================================');
        DB::beginTransaction();

        //Validation & Store receipt information
        $receiptBase64Data = $input['receipt'];
        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);
        $response = null;
        try {
            $response = $validator->setReceiptData($receiptBase64Data)->setSharedSecret(env('SHARED_SECRET_KEY'))->validate();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), self::HTTP_BAD_REQUEST, $input);
        }

        if ($response instanceof AbstractResponse && $response->isValid())
        {
            $package = Packages::where('status', '=', '1')->where('package', $package_type)->first();
            if (empty($package)) {
                Log::error('====================================ERROR-PACKAGE-NOT-FOUND=========================================' . $type);
                return $this->error("package_not_found", 206);
            }

            $package_data = json_decode($package->description);

            $purchase = $response->getPurchases()[0];
            $payment_transaction_id = $purchase->getTransactionId();
            //$payment_product_id = $purchase->getProductId(); // Item id
            //$payment_product_id = 'com.calling_1_month_nonsub';
            $payment_product_id = 'com.calling_10_minutes_con';

            $paid = Payment::where('user_id', $user_id)->where('detail', $payment_transaction_id)->first();
            Log::warning('Response first transaction:' . $payment_transaction_id);

            $arrPackage = [];
            foreach ($package_data as $data) {
                $arrPackage[$data->id] = $data;
            }
            
            if (!array_key_exists($payment_product_id, $arrPackage)) {
                Log::warning('==================Item data not match with ' . $data->id . ' =====================');
                Log::warning('==================Item data from app :' . $payment_product_id . '============================================================');
                return $this->error("Item id does not exist in system", 206, $payment_product_id);
            }

            $plan = $package_type . '-' . $arrPackage[$payment_product_id]->minute;
            $cost = str_replace('¥', '', $arrPackage[$payment_product_id]->cost);
            $total = str_replace(',', '', $cost);

            Log::warning('================Transaction has accept: Plan:' . $plan . '-Cost:' . $cost . 'Total:' . $total . ' ======================');
            $paymentExpiredDate = $purchase->getExpiresDate(); //date('Y-m-d H:i:s', $purchase->getExpiresDate());
            $today = date('Y-m-d H:i:s');            
            $isExpired = $paymentExpiredDate < $today ? true : false;

            if (($paid && $package_type != Packages::TYPE_PACKAGE_MINUTE) || ($package_type != Packages::TYPE_PACKAGE_MINUTE && $isExpired)) {
                if($paid){
                    Log::warning('==========================================Transaction has exist:' . $paid->detail . ' ===========================');
                    Log::warning('==========================================With payment history id:' . $paid->id . ' =============================');
                }
                return $this->error("Your payment is not accepted.", 206);
            }

            // update payment
            $param = [
                'user_id' => $user_id,
                'plan' => $plan,
                'total' => $total,
                'package' => $package_type,
                'detail' => isset($payment_transaction_id) ? $payment_transaction_id : $purchase->getOriginalTransactionId(),
                'receipt_data' => json_encode($response->getRawData()),
                'purchase_date' => $purchase->offsetExists('purchase_date_ms') ? $purchase->getPurchaseDate() : $purchase->getOriginalPurchaseDate(),
                'is_package_trial' => Packages::PACKAGE_TRIAL_DEFAULT,
                'purchase_expired_date' => date('Y-m-d H:i:s', $paymentExpiredDate)
            ];

            //update is package trial
            if ($package_type == Packages::TYPE_PACKAGE_AUTO_PAYMENT && !in_array($requestType, [Packages::REQUEST_TYPE_RESTORE, Packages::REQUEST_TYPE_RENEW])) {
                //check if user used package trial before
                $userPaid = Payment::withTrashed()
                    ->where('user_id', $user_id)
                    ->where('package', Packages::TYPE_PACKAGE_AUTO_PAYMENT)
                    ->where('is_package_trial', Packages::PACKAGE_IS_TRIAL)
                    ->count();

                if ($userPaid == 0) {
                    $param['is_package_trial'] = Packages::PACKAGE_IS_TRIAL;
                }
            }
            $payment = $this->updatePayment($param);

            Log::info('Success_insert_payment_history' . $payment->id);

            // Check request type from both 2 versions old and new version of iOS app
            switch ($package_type) {
                case Packages::TYPE_PACKAGE_MONTH:
                case Packages::TYPE_PACKAGE_AUTO_RENEWAL:
                    if (!in_array($requestType, [Packages::REQUEST_TYPE_RESTORE])) {
                        $user->total_call += Packages::MINUTE_ADD_PACKAGE;
                    }
                    break;
                case Packages::TYPE_PACKAGE_MONTH_RENEW:
                case Packages::TYPE_PACKAGE_AUTO_PAYMENT:
                    if (!in_array($requestType, [Packages::REQUEST_TYPE_RESTORE])) {
                        $user->total_call += Packages::MINUTE_ADD_PACKAGE;
                    }
                    break;
                default:
                    if (!in_array($requestType, [Packages::REQUEST_TYPE_RESTORE])) {
                        //$user->remain += $arrPackage[$payment_product_id]->minute;
                    }
                    break;
            }

            Log::info("Success_insert_payment_history {$package_type} {$param['purchase_date']}");

            //$user->reset_date = $payment->created_at;
            $user->save();

            Log::info('Success_update_user_with_type_payment :' . $package_type . 'user_id :' . $user->id);

            DB::commit();
            //$user_data = get_profile($user_id);

            Log::warning('=========================================Plan type: ' . $package_type . '=========================================');
            Log::warning('========================Response payment history : ' . json_encode($payment) . '===============================');
            Log::info('=========================END LOG FOR USER : ' . $user_id . '=============================================================');

            return $this->success($response);
        }

        Log::warning('===========================There has problem while purchase:===========================');
        Log::warning('===========================Apple return receipt status code: ' . $response->getResultCode() . '==========================');

        return $this->error('There has problem while purchase');
    }

    /**
     * Insert payment
     *
     * @param $param
     * @return \Payment
     */
    private function updatePayment($param)
    {
        $payment = new Payment();
        $payment->user_id = $param['user_id'];
        $payment->plan = $param['plan'];
        $payment->status = 1;
        $payment->total = $param['total'];
        $payment->package = $param['package'];
        $payment->detail = $param['detail'];
        $payment->receipt_data = $param['receipt_data'];
        $payment->purchase_date = $param['purchase_date'];
        $payment->is_package_trial = $param['is_package_trial'];
        $payment->purchase_expired_date = $param['purchase_expired_date'];
        $payment->save();

        return $payment;
    }

    //
    public function addMorePointForUser(Request $request)
    {
        try {
            $input = $request->all();
            $rules = [
                'receipt' => 'required|string',
            ];
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                return $this->error($validator->errors(), self::HTTP_BAD_REQUEST, $input);
            }   
            
            $user_id = $this->_getId();
            $user = User::find($user_id);
            if (!$user) {
                return $this->error($this->errors(), self::HTTP_BAD_REQUEST, null);
            }

            $receiptBase64Data = $input['receipt'];
            $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);
            $response = null;

            DB::beginTransaction();
            Log::info('=========================START PURCHASE POINT FOR USER: ' . $user_id . '==========================');
            
            $userObject = User::where('sex', 1)->find($user_id);
            if (empty($userObject)) {
                return $this->error('Your account not match in database');
            }          

            Log::warning("===========================REQUESTED PAYMENT API:" . iTunesValidator::ENDPOINT_PRODUCTION);

            $response = $validator->setReceiptData($receiptBase64Data)->validate();

            Log::info("Receipt " . json_encode($response));

            if ($response instanceof AbstractResponse && $response->isValid()) {               
                $package = Packages::where('status', '=', '1')->where('package', self::MINUTE_PACKAGE_TYPE)->first();

                if (empty($package)) {
                    Log::error('=====================ERROR-PACKAGE-NOT-FOUND==================' . self::MINUTE_PACKAGE_TYPE);
                    return $this->error(t("package_not_found"), 206);
                }

                $purchase = $response->getPurchases()[0];
                $payment_transaction_id = $purchase->getTransactionId();
                //$payment_product_id = $purchase->getProductId(); // Item id
                $payment_product_id = 'com.calling_10_minutes_con';

                Log::warning('Response first transaction:' . $purchase->getTransactionId() . ' - - - ');
                $packageItems = json_decode($package->description);

                $arrPackage = [];
                foreach ($packageItems as $item) {
                    $arrPackage[$item->id] = $item;
                }

                if (!array_key_exists($payment_product_id, $arrPackage)) {
                    Log::warning('==================ITEM NOT MATCH=====================');
                    Log::warning('==================Item data from app :' . $payment_product_id . '============================================================');
                    return $this->error("Your item id is not match.", 206);
                }

                $plan = self::MINUTE_PACKAGE_TYPE . '-' . $arrPackage[$payment_product_id]->minute;
                $cost = str_replace('¥', '', $arrPackage[$payment_product_id]->cost);
                $total = str_replace(',', '', $cost);

                Log::warning('================Transaction has accept: Plan:' . $plan . '-Cost:' . $cost . 'Total:' . $total . ' ======================');

                $param = [
                    'user_id' => $user_id,
                    'plan' => $plan,
                    'total' => $total,
                    'package' => self::MINUTE_PACKAGE_TYPE,
                    'detail' => isset($payment_transaction_id) ? $payment_transaction_id : $purchase->getOriginalTransactionId(),
                    'receipt_data' => json_encode($response->getRawData()),
                    'purchase_date' => $purchase->offsetExists('purchase_date_ms') ? $purchase->getPurchaseDate() : $purchase->getOriginalPurchaseDate(),
                    'purchase_expired_date' => $purchase->getPurchaseDate(),
                    'is_package_trial' => Packages::PACKAGE_TRIAL_DEFAULT
                ];
                
                $payment = $this->_makePayment($param);
                Log::info('Success_insert_payment_history'.$payment->id);
                
                $userObject->point += $arrPackage[$payment_product_id]->minute;
                $userObject->save();
                DB::commit();
                Log::info('PURCHASE SUCCESSFULLY:'.$payment->id);

                //$user_data = get_profile($userId);
                return $this->success($payment);
            }
            return $this->error('There has problem while purchase');
        } catch (\Exception $e) {
            Log::error('===========================[ERROR AND ROLLBACK TRANSACTION==========================');
            Log::error($e->getMessage());
            DB::rollback();
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create Payment History record
     * @param $params
     * @return \Payment
     */
    protected function _makePayment($params)
    {
        $payment = new Payment();
        $payment->user_id = $params['user_id'];
        $payment->plan = $params['plan'];
        $payment->status = 1;
        $payment->total = $params['total'];
        $payment->package = $params['package'];
        $payment->detail = $params['detail'];
        $payment->receipt_data = $params['receipt_data'];
        $payment->purchase_date = $params['purchase_date'];
        $payment->is_package_trial = $params['is_package_trial'];
        $payment->purchase_expired_date = $params['purchase_expired_date'];
        $payment->save();

        return $payment;
    }

    /**
     * Get Apple package
     * @return mixed
     */
    public function get_packages(Request $request)
    {
        $input = $request->all();
        $rules = [
            'package_type' => 'required|in:' . Packages::TYPE_PACKAGE_MONTH . ',' . Packages::TYPE_PACKAGE_MINUTE . ',' . Packages::TYPE_PACKAGE_MONTH_RENEW . ',' . Packages::TYPE_PACKAGE_AUTO_RENEWAL . ',' . Packages::TYPE_PACKAGE_AUTO_PAYMENT,
        ];
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return $this->error($validator->errors(), self::HTTP_BAD_REQUEST, $input);
        }   

        $package = Packages::where('status','=','1')->where('package', $input['package_type'])->first();
        $package_data = json_decode($package->description);

        $arr_data = [];
        foreach($package_data as $data)
        {
            $cost = str_replace('¥', '', $data->cost) ;
            $data->plan = $cost . '円で' . $data->name;
            $arr_data[] = $data;
        }

        return $this->success($arr_data);
    }

}