<?php

namespace App\Repositories;

use App\Models\Make;
use App\Models\VehicleModel;
use App\Models\Trim;
use App\Models\MakeMapping;
use App\Models\ModelMapping;
use App\Models\TrimMapping;
use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;
use Illuminate\Support\Collection;

class VehicleMappingRepository implements VehicleMappingRepositoryInterface
{
    protected Make $make;
    protected VehicleModel $model;
    protected Trim $trim;
    protected MakeMapping $makeMapping;
    protected ModelMapping $modelMapping;
    protected TrimMapping $trimMapping;
    public function __construct(
        Make $make,
        VehicleModel $model,
        Trim $trim,
        MakeMapping $makeMapping,
        ModelMapping $modelMapping,
        TrimMapping $trimMapping
    ) {
        $this->make = $make;
        $this->model = $model;
        $this->trim = $trim;
        $this->makeMapping = $makeMapping;
        $this->modelMapping = $modelMapping;
        $this->trimMapping = $trimMapping;
    }

    public function getMakes(): Collection
    {
        // Return makes from Make table that have active mappings
        return $this->makeMapping
            ->where('status', 'active')
            ->distinct()
            ->get(['insurer_make_id', 'insurer_make_name']);
    }

    public function getModelsByMake(?string $makeCode): Collection
    {
        // Return models from VehicleModel table that are mapped for the given make_id
        return $this->modelMapping
            ->where('insurer_make_id', $makeCode)
            ->where('status', 'active')
            ->distinct()
            ->get(['insurer_model_id', 'insurer_model_name']);
    }

    public function getTrimsByModel(?string $modelCode): Collection
    {
        // Return trims from Trim table that are mapped for the given model_id
        return $this->trimMapping
            ->where('insurer_model_id', $modelCode)
            ->where('status', 'active')
            ->distinct()
            ->get(['insurer_trim_id', 'insurer_trim_name']);
    }

    public function findMakeByName(?string $name): ?Make
    {
        return $this->make->where('make_name', $name)->first();
    }

    public function findModelByName(?string $name): ?VehicleModel
    {
        return $this->model->where('model_name', $name)->first();
    }

    public function findTrimByName(?string $name): ?Trim
    {
        return $this->trim->where('trim_name', $name)->first();
    }

    public function findMakeMapping(int $insurerId, ?int $makeId): ?MakeMapping
    {
        return $this->makeMapping->where('insurer_id', $insurerId)
            ->where('dr_make_id', $makeId)
            ->where('status', 'active')
            ->first();
    }

    public function findModelMapping(int $insurerId, ?int $modelId): ?ModelMapping
    {
        return $this->modelMapping->where('insurer_id', $insurerId)
            ->where('dr_model_id', $modelId)
            ->where('status', 'active')
            ->first();
    }

    public function findTrimMapping(int $insurerId, ?int $trimId): ?TrimMapping
    {
        return $this->trimMapping->where('insurer_id', $insurerId)
            ->where('dr_trim_id', $trimId)
            ->where('status', 'active')
            ->first();
    }
}
