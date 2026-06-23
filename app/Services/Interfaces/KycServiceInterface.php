<?php

namespace App\Services\Interfaces;

interface KycServiceInterface
{
    /**
     * Verify CKYC details and log the attempt.
     */
    public function verifyKyc(array $payload): array;
}
