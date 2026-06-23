<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\KycServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KycController extends Controller
{
    public function __construct(private readonly KycServiceInterface $kycService) {}

    public function verify(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quote_id' => 'required|string',
                'id_num' => 'required|string|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i',
                'customer_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->kycService->verifyKyc($request->all());

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process KYC verification: ' . $e->getMessage()
            ], 500);
        }
    }
}
