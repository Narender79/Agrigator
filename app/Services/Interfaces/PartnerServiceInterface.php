<?php

namespace App\Services\Interfaces;

interface PartnerServiceInterface
{
    public function createPartner(array $payload): array;
    public function updatePartner(array $payload, int $id): array;
    public function showPartnerByUserId(int $userId);
    public function createPartnerCredential(array $payload): array;
    public function getPartnerCredentialByPartnerId(int $partnerId);
}
