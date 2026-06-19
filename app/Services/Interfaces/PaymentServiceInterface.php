<?php

namespace App\Services\Interfaces;

interface PaymentServiceInterface
{
    /**
     * Handles business logic for fetching insurer proposal ID and saving local records.
     */
    public function updatePaymentStatus(array $payload): array;
}
