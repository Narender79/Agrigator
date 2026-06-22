<?php

namespace App\Repositories;

use App\Models\Insurance\Insurer;
use App\Models\Insurance\InsurerIntegration;
use App\Repositories\Interfaces\InsurerRepositoryInterface;

class InsurerRepository implements InsurerRepositoryInterface
{
    public function getActiveByName(string $name): ?Insurer
    {
        return Insurer::where('company_name', $name)
            ->where('status', 'active')
            ->first();
    }

    public function getIntegrationByInsurerId(int $insurerId): ?InsurerIntegration
    {
        return InsurerIntegration::where('insurer_id', $insurerId)->first();
    }
}
