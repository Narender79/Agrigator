<?php

namespace App\Repositories\Interfaces;

use App\Models\Insurance\Proposal;

interface ProposalRepositoryInterface
{
    /**
     * Save the proposal details inside the SQL database.
     */
    public function saveProposal(array $data): Proposal;
}
