<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\KycServiceInterface;
use Illuminate\Http\Request;
use App\Utils\Response;
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
                return Response::validationFailed($validator->errors());
            }

            $result = $this->kycService->verifyKyc($request->all());

            if (isset($result['success']) && $result['success'] === false) {
                return Response::failed(data_get($result, 'message', 'KYC verification failed.'), 400, [], 400);
            }
            return Response::success(data_get($result, 'data'));
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }
}
