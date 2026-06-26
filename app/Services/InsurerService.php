<?php

namespace App\Services;

use App\Repositories\Interfaces\InsurerRepositoryInterface;
use App\Services\Interfaces\InsurerServiceInterface;

class InsurerService implements InsurerServiceInterface
{
    public function __construct(private InsurerRepositoryInterface $insurerRepo) {}

    public function createInsurer(array $data): array
    {
        return $this->insurerRepo->createInsurer($data);
    }
    public function updateInsurer(array $data, int $id): array
    {
        return $this->insurerRepo->updateInsurer($data, $id);
    }

    public function getInsurerByUserId(int $userId): array
    {
        return $this->insurerRepo->showInsurerByUserId($userId, false);
    }
}
