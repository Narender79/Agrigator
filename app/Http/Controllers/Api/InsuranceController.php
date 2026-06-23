<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\InsuranceServiceInterface;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Utils\Response;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Validator;

class InsuranceController extends Controller
{
    public function __construct(private readonly InsuranceServiceInterface $insuranceService) {}

    public function getQuotesByQuoteId(string $quoteId)
    {
        try {
            $user_id = request()->attributes->get('merchant_partner')->user_id;
            $result = $this->insuranceService->getQuoteByQuoteId([
                "quote_id" => $quoteId,
                "merchant_id" => $user_id
            ]);
            return Response::success($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching quotes: ' . $e->getMessage()
            ], 500);
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
