<?php

namespace App\Repositories\Interfaces;

use App\Models\Insurance\Insurer;
use App\Models\Insurance\InsurerIntegration;

interface PartnerRepositoryInterface
{
    public function createPartner(array $payload): array;
    public function updatePartner(array $payload, int $id): array;
    public function showPartnerByUserId(int $userId, bool $objReturn = true);

    public function createPartnerCredential(array $payload): array;
    public function getPartnerCredentialByPartnerId(int $partnerId, bool $objReturn = true);
}
