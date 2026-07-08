<?php

namespace App\Repositories;

use App\Models\Insurance\UsersInsurance;
use App\Repositories\Interfaces\UsersInsuranceRepositoryInterface;
use Override;

class UsersInsuranceRepository implements UsersInsuranceRepositoryInterface
{
    public function __construct(
        private UsersInsurance $userInsModel
    ) {}

    public function findByProposalIdOrQuoteNo(string $proposalId, ?string $quoteNo): ?UsersInsurance
    {
        return $this->userInsModel->where('proposal_id', $proposalId)
            ->when($quoteNo, function ($query, $quoteNo) {
                return $query->orWhere('quote_no', $quoteNo);
            })
            ->first();
    }

    public function updateOrCreateByUniqueId(string $uniqueId, array $data): UsersInsurance
    {
        return $this->userInsModel->updateOrCreate(
            ['insurance_unique_id' => $uniqueId],
            $data
        );
    }

    public function findByQuoteId(string $quoteId): ?UsersInsurance
    {
        return $this->userInsModel->where('quote_id', $quoteId)->first();
    }

    #[Override]
    public function findActivePolicyByRegistrationNo(string $registrationNo): ?UsersInsurance
    {
        return $this->userInsModel->where('registration_no', $registrationNo)
            ->whereNotNull('policy_no')
            ->where('policy_no', '!=', '')
            ->first();
    }
}
