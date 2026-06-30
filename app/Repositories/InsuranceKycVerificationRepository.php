<?php

namespace App\Repositories;

use App\Repositories\Interfaces\InsuranceKycVerificationRepositoryInterface;
use App\Models\Insurance\InsuranceKycVerification;
use Override;

class InsuranceKycVerificationRepository implements InsuranceKycVerificationRepositoryInterface
{

    public function __construct(private InsuranceKycVerification $insuranceKycModel) {}

    /**
     * Log or update a KYC verification attempt.
     */
    public function savekycVerification(array $data): InsuranceKycVerification
    {
        return $this->insuranceKycModel->updateOrCreate(
            ['session_id' => $data['session_id']],
            $data
        );
    }

    /**
     * Find the latest KYC verification attempt for a session.
     */
    public function findLatestBySession(string $sessionId): ?InsuranceKycVerification
    {
        return $this->insuranceKycModel->where('session_id', $sessionId)->latest()->first();
    }
}
