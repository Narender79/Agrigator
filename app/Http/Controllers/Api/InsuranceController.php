<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\InsuranceServiceInterface;
use App\Utils\Response;

class InsuranceController extends Controller
{
    public function __construct(private readonly InsuranceServiceInterface $insuranceService) {}

    public function getQuotesByQuoteId(string $quoteId)
    {
        try {
            $result = $this->insuranceService->getQuoteByQuoteId($quoteId);
            return Response::success($result);
        } catch (\Exception $e) {
           return Response::exeptionResponse($e);
        }
    }
}
