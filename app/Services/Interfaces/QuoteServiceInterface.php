<?php

namespace App\Services\Interfaces;

interface QuoteServiceInterface
{
    public function getInsurerQuote(string $insurerSlug, array $payload): array;
}
