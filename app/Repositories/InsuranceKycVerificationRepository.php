<?php

namespace App\Repositories;

use App\Repositories\Interfaces\InsuranceKycVerificationRepositoryInterface;
use App\Models\Insurance\InsuranceKycVerification;
use Override;

class InsuranceKycVerificationRepository implements InsuranceKycVerificationRepositoryInterface
{
    /**
     * Log or update a KYC verification attempt.
     */


    public function savekycVerification(array $data): InsuranceKycVerification
    {
        return InsuranceKycVerification::updateOrCreate(
            ['session_id' => $data['session_id']],
            $data
        );
    }

    /**
     * Find the latest KYC verification attempt for a session.
     */
    public function findLatestBySession(string $sessionId): ?InsuranceKycVerification
    {
        return InsuranceKycVerification::where('session_id', $sessionId)->latest()->first();
    }
}
