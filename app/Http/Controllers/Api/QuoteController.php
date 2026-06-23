<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Utils\Response;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Validator;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteServiceInterface $quoteService)
    {
    }

    public function store(Request $request)
    {
        try {
            // Defining common required fields for both 2W and 4W
            $rules = [
                // 'make_id'             => 'required|integer',
                // 'model_id'            => 'required|integer',
                // 'trim_id'             => 'required|integer',
                'make_name' => 'required|string',
                'model_name' => 'required|string',
                'trim_name' => 'required|string',
                // 'q_producer_code'     => 'required|string',
                'man_year' => 'required',
                // 'pol_start_date'      => 'required|date_format:Y-m-d',
                'place_reg' => 'required|string',
                // 'proposer_type'       => 'required|string',
                'proposer_pincode' => 'required|string',
                'business_type_no' => 'required|string',
                'dor' => 'required|date_format:Y-m-d',
                // 'prev_pol_type'       => 'required|string',
                'claim_last'          => 'required|string',
                'pre_pol_ncb'         => 'required|string',
                'reg_no'             => 'required|string',
                // 'product_code'        => 'required|string',
                '__finalize' => 'required|string',
                'vehicle_type' => 'required|string'
            ];

            // Conditional validation based on vehicle product code
            if ($request->input('vehicle_type') === '2w') {
                // 2W specific fields
                $rules['pol_tenure'] = 'required|string';
                $rules['plan_type'] = 'required|string';
                $request->request->set('product_code', '3187');
            } else {
                // 4W specific fields
                $rules['pol_plan_id'] = 'required|string';
                $rules['motor_plan_opted_no'] = 'required|string';
                $request->request->set('pol_plan_variant', 'PackagePolicy');
                $request->request->set('product_code', '3184');
            }
            // Runing validation.
            $validated = Validator::make($request->all(), $rules);
            if ($validated->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validated->errors()
                ], 422);
            }
            $result = $this->quoteService->getInsurerQuote($request->all());
            if (isset($result['success']) && $result['success'] === false) {
                return Response::failed(data_get($result, 'errors'), 500, array('data' => $result));
            }
            return Response::success($result);
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }

    public function availableOptions(Request $request)
    {
        try {
            $vehicleType = $request->input('vehicle_type');

            $vehicleType = empty($vehicleType) ? '4w' : $vehicleType;

            $result['available_options'] = [
                'policy_plans' => config("insurance_masters.policy_plans.{$vehicleType}", []),
                'motor_plans' => config("insurance_masters.motor_plans.{$vehicleType}", []),
                'add_ons' => config("insurance_masters.add_ons.{$vehicleType}", []),
                'business_types' => config('insurance_masters.business_types'),
                'prev_policy_types' => config('insurance_masters.prev_policy_types'),
                'prev_policy_ncbs' => config('insurance_masters.prev_policy_ncbs'),
                'voluntary_amounts' => config('insurance_masters.voluntary_amounts'),
                'nominee_relationships' => config('insurance_masters.nominee_relationships'),
                'appointee_relationships' => config('insurance_masters.appointee_relationships'),
                'financier_types' => config('insurance_masters.financier_types'),
                'tyre_secure_options' => config('insurance_masters.tyre_secure_options'),
                'engine_secure_options' => config('insurance_masters.engine_secure_options'),
                'owner_driver_declarations' => config('insurance_masters.owner_driver_declarations'),
                'occupations' => config('insurance_masters.occupations'),
            ];

            return Response::success($result);
        } catch (\Throwable $th) {
            return Response::exeptionResponse($th);
        }
    }

    public function saveQuotePayment(Request $request)
    {
        try {
            $rules = [
                'insurance_unique_id'           => 'string',
                'quote_id'          => 'required|string',
                'droom_payment_id'           => 'required|string',
                'gateway_txn_id'            => 'required',
                'payment_status'           => 'required|string',
                'payment_id'    => 'required|string',
                'selling_price'    => 'required',
            ];
            // Runing validation.
            $validated = Validator::make($request->all(), $rules);
            if ($validated->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validated->errors()
                ], 422);
            }
            $result = $this->quoteService->updateQuotePaymentStatus($request->all());
            if (isset($result['success']) && $result['success'] === false) {
                return Response::failed(data_get($result, 'errors'), 500);
            }
            return Response::success(data_get($result, 'data'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching quotes: ' . $e->getMessage()
            ], 500);
        }
    }
}
