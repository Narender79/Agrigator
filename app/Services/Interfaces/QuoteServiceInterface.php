<?php

namespace App\Services\Interfaces;

interface QuoteServiceInterface
{
    public function getInsurerQuote(array $payload): array;
}
