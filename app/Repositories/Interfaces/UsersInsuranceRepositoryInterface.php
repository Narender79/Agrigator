<?php

namespace App\Repositories\Interfaces;

use App\Models\Insurance\UsersInsurance;

interface UsersInsuranceRepositoryInterface
{
    public function findByProposalIdOrQuoteNo(string $proposalId, ?string $quoteNo): ?UsersInsurance;

    public function updateOrCreateByUniqueId(string $uniqueId, array $data): UsersInsurance;
}
