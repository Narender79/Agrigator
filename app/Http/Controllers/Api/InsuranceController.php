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
           return Response::exeptionResponse($e);
        }
    }
}
