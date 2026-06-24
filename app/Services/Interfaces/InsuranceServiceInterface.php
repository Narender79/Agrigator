<?php

namespace App\Services\Interfaces;

interface InsuranceServiceInterface
{
    public function getQuoteByQuoteId(string $quoteId): array;
}
