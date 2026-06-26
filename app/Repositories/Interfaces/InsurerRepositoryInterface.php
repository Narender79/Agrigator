<?php

namespace App\Repositories\Interfaces;

use App\Models\Insurance\Insurer;
use App\Models\Insurance\InsurerIntegration;

interface InsurerRepositoryInterface
{
    public function getActiveByName(string $name): ?Insurer;
    public function getIntegrationByInsurerId(int $insurerId): ?InsurerIntegration;
    public function createInsurer(array $payload): array;
    public function updateInsurer(array $payload, int $id): array;
    public function showInsurerByUserId(int $userId, bool $objReturn = true);
}
