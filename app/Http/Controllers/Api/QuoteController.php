<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Utils\Response;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Validator;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteServiceInterface $quoteService) {}

    public function store(Request $request)
    {
        try {
            // Defining common required fields for both 2W and 4W
            $rules = [
                // 'make_id'             => 'required|integer',
                // 'model_id'            => 'required|integer',
                // 'trim_id'             => 'required|integer',
                'make_name'           => 'required|string',
                'model_name'          => 'required|string',
                'trim_name'           => 'required|string',
                'q_producer_code'     => 'required|string',
                'man_year'            => 'required',
                'pol_start_date'      => 'required|date_format:Y-m-d',
                'place_reg'           => 'required|string',
                'proposer_type'       => 'required|string',
                'proposer_pincode'    => 'required|string',
                'business_type_no'    => 'required|string',
                'dor'                 => 'required|date_format:Y-m-d',
                'prev_pol_type'       => 'required|string',
                'claim_last'          => 'required|string',
                'pre_pol_ncb'         => 'required|string',
                'regno_1'             => 'required|string',
                'regno_2'             => 'required|string',
                'regno_3'             => 'required|string',
                'regno_4'             => 'required|string',
                // 'product_code'        => 'required|string',
                '__finalize'          => 'required|string',
            ];

            // Conditional validation based on vehicle product code
            if ($request->input('product_code') === '3187') {
                // 2W specific fields
                $rules['pol_tenure'] = 'required|string';
                $rules['plan_type']  = 'required|string';
            } else {
                // 4W specific fields
                $rules['pol_plan_id']          = 'required|string';
                $rules['pol_plan_variant']     = 'required|string';
                $rules['motor_plan_opted_no']  = 'required|string';
            }
            // Runing validation.
            $validated = Validator::make($request->all(), $rules);
            if ($validated->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validated->errors()
                ], 422);
            }
            $result = $this->quoteService->getInsurerQuote($request->all());
            if (empty($result)) {
                return Response::failed(data_get($result, 'errors'), 500, array('data' => $result));
            }
            return Response::success($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching quotes: ' . $e->getMessage()
            ], 500);
        }
    }
}
