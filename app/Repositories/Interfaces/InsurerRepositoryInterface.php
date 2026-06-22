<?php

namespace App\Repositories\Interfaces;

use App\Models\Insurance\Insurer;
use App\Models\Insurance\InsurerIntegration;

interface InsurerRepositoryInterface
{
    public function getActiveByName(string $name): ?Insurer;
    public function getIntegrationByInsurerId(int $insurerId): ?InsurerIntegration;
}
