<?php

namespace App\Repositories;

use App\Models\Insurance\UsersInsurance;
use App\Repositories\Interfaces\UsersInsuranceRepositoryInterface;

class UsersInsuranceRepository implements UsersInsuranceRepositoryInterface
{

    public function findByProposalIdOrQuoteNo(string $proposalId, ?string $quoteNo): ?UsersInsurance
    {
        return UsersInsurance::where('proposal_id', $proposalId)
            ->when($quoteNo, function ($query, $quoteNo) {
                return $query->orWhere('quote_no', $quoteNo);
            })
            ->first();
    }

    public function updateOrCreateByUniqueId(string $uniqueId, array $data): UsersInsurance
    {
        return UsersInsurance::updateOrCreate(
            ['insurance_unique_id' => $uniqueId],
            $data
        );
    }

    public function findByQuoteId(string $quoteId): ?UsersInsurance
    {
        return UsersInsurance::where('quote_id', $quoteId)->first();
    }
}
