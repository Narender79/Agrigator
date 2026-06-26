<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;
use App\Models\Make;
use App\Models\VehicleModel;
use App\Models\Trim;
use App\Models\MakeMapping;
use App\Models\ModelMapping;
use App\Models\TrimMapping;

interface VehicleMappingRepositoryInterface
{
    public function getMakes(?string $vehicleType): Collection;

    public function getModelsByMake(?string $makeCode): Collection;

    public function getTrimsByModel(?string $modelCode): Collection;

    public function findMakeByName(?string $name): ?Make;
    public function findModelByName(?string $name, ?int $makeId = null): ?VehicleModel;
    public function findTrimByName(?string $name, ?int $modelId = null): ?Trim;
    public function findMakeMapping(int $insurerId, ?int $makeId): ?MakeMapping;
    public function findModelMapping(int $insurerId, ?int $modelId): ?ModelMapping;
    public function findTrimMapping(int $insurerId, ?int $trimId): ?TrimMapping;
}
