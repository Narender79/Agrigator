<?php

namespace App\Services;

use App\Repositories\Interfaces\PartnerRepositoryInterface;
use App\Services\Interfaces\PartnerServiceInterface;

class PartnerService implements PartnerServiceInterface
{
    public function __construct(private PartnerRepositoryInterface $partnerRepo) {}

    public function createPartner(array $data): array
    {
        return $this->partnerRepo->createPartner($data);
    }
    public function updatePartner(array $data, int $id): array
    {
        return $this->partnerRepo->updatePartner($data, $id);
    }

    public function showPartnerByUserId(int $userId): array
    {
        return $this->partnerRepo->showPartnerByUserId($userId, false);
    }

    public function createPartnerCredential(array $data): array
    {
        return $this->partnerRepo->createPartner($data);
    }

    public function getPartnerCredentialByPartnerId(int $partnerId): array
    {
        return $this->partnerRepo->getPartnerCredentialByPartnerId($partnerId, false);
    }
}
