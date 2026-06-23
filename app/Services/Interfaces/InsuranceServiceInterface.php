<?php

namespace App\Services\Interfaces;

interface InsuranceServiceInterface
{
    public function getQuoteByQuoteId(array $payload): array;
}
