<?php

namespace App\Services;

use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Services\Interfaces\InsuranceServiceInterface;
use Exception;

class InsuranceService implements InsuranceServiceInterface
{
    public function __construct(private QuoteRepositoryInterface $quoteRepo) {
    }

    public function getQuoteByQuoteId(array $payloads): array
    {
        return $this->quoteRepo->getInsuranceDataByQuoteID($payloads);
    }
}
