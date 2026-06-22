<?php

namespace App\Services\Interfaces;

interface QuoteServiceInterface
{
    public function getInsurerQuote(array $payload): array;
    public function updateQuotePaymentStatus(array $payload): array;
}
