<?php

namespace App\Services\Interfaces;

use App\Models\Insurance\UsersInsurance;
use App\Models\User;

interface PaymentServiceInterface
{
    /**
     * Verify payment with the respective insurer.
     *
     * @param UsersInsurance $record
     * @return array
     */

    public function verifyInsurerPayment(UsersInsurance $record): array;
    public function verifyInsurerPaymentByQuoteId(string $quoteId): array;
    public function downloadPolicyByQuoteId(string $quoteId): array;
}
