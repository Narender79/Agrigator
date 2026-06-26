<?php

namespace App\Services;

use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Services\Interfaces\InsuranceServiceInterface;

class InsuranceService implements InsuranceServiceInterface
{
    public function __construct(private QuoteRepositoryInterface $quoteRepo) {
    }

    public function getQuoteByQuoteId(string $quoteId): array
    {
        $user_id = request()->attributes->get('merchant_partner')->user_id;
        return $this->quoteRepo->getInsuranceDataByQuoteID([
                "quote_id" => $quoteId,
                "merchant_id" => $user_id
            ]);
    }
}
