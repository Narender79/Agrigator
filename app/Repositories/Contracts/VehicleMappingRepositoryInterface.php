<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface VehicleMappingRepositoryInterface
{
    public function getActiveMakes(): Collection;

    public function getActiveModelsByMake(?string $makeCode, ?string $makeName): Collection;

    public function getActiveTrimsByModel(?string $modelCode, ?string $modelName): Collection;
}
