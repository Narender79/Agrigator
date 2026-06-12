<?php

namespace App\Repositories;

use App\Repositories\Interfaces\ProposalRepositoryInterface;
use App\Models\Insurance\Proposal;

class ProposalRepository implements ProposalRepositoryInterface
{
    public function saveProposal(array $data): Proposal
    {
        return Proposal::create($data);
    }
}
