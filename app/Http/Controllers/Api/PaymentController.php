<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Interfaces\PaymentServiceInterface;
use App\Utils\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentServiceInterface $paymentService) {}

    public function verifyInsurerPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quote_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return Response::validationFailed($validator->errors());
            }

            $result = $this->paymentService->verifyInsurerPaymentByQuoteId($request->quote_id);
            // return Response::success(data_get($result, 'data'), '');
            return Response::success($result, ['message' => 'Insurer payment verified successfully.']);
        } catch (BusinessException $e) {
            return Response::failed($e->getMessage(), $e->getStatusCode(), [], $e->getStatusCode());
        } catch (\Throwable $th) {
            return Response::exeptionResponse($th);
        }
    }

    public function downloadPolicy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quote_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return Response::validationFailed($validator->errors());
            }

            $result = $this->paymentService->downloadPolicyByQuoteId($request->quote_id);

            return Response::success($result, ['message' => 'Policy document retrieved successfully.']);
        } catch (BusinessException $e) {
            return Response::failed($e->getMessage(), $e->getStatusCode(), [], $e->getStatusCode());
        } catch (\Throwable $th) {
            return Response::exeptionResponse($th);
        }
    }
}
