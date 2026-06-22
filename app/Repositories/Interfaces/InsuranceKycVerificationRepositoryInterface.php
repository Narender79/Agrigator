<?php

namespace App\Repositories\Interfaces;

use App\Models\Insurance\InsuranceKycVerification;


interface InsuranceKycVerificationRepositoryInterface
{
    /**
     * Log or update a KYC verification attempt.
     */
    public function savekycVerification(array $data): InsuranceKycVerification;

    /**
     * Find the latest KYC verification attempt for a session.
     */
    public function findLatestBySession(string $sessionId): ?InsuranceKycVerification;
}
