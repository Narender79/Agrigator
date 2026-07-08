<?php

namespace App\Repositories\Interfaces;

// use App\Models\Insurance\Quote;
use \App\Models\Insurance\QuoteRequest;
use App\Models\Insurance\UsersInsurance;
use \App\Models\VehicleHistory;

interface QuoteRepositoryInterface
{
    /**
     * Store a new quote request/response locally inside MongoDB.
     *
     * @param array $data
     * @return string The generated MongoDB ObjectId string
     */
    public function saveQuote(array $data): string;
    public function checkValidQuoteID(string $quoteId): UsersInsurance;
    public function updatePaymentStatus(array $data): UsersInsurance;
    public function firstOrCreateQuoteRequest(string $sessionId, array $data): QuoteRequest;
    public function getInsuranceDataByQuoteID(array $data): array;
    public function getVehicleHistoryByRegistrationNo(string $regNo): ?VehicleHistory;
}
