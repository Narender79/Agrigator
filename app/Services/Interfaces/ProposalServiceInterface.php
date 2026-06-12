<?php

namespace App\Services\Interfaces;

interface ProposalServiceInterface
{
    /**
     * Handles business logic for fetching insurer proposal ID and saving local records.
     */
    public function createProposal(array $payload): array;
}
