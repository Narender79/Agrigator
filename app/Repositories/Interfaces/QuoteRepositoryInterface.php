<?php

namespace App\Repositories\Interfaces;

use App\Models\Insurance\Quote;
use \App\Models\Insurance\QuoteRequest;

interface QuoteRepositoryInterface
{
    /**
     * Store a new quote request/response locally inside MongoDB.
     *
     * @param array $data
     * @return string The generated MongoDB ObjectId string
     */
    public function saveQuote(array $data): string;
    public function firstOrCreateQuoteRequest(string $sessionId, array $data): QuoteRequest;
}
