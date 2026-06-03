<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface VehicleMappingRepositoryInterface
{
    public function getMakes(): Collection;

    public function getModelsByMake(?string $makeCode): Collection;

    public function getTrimsByModel(?string $modelCode): Collection;
}
