<?php

namespace App\Repositories\Eloquent;

use App\Models\Make;
use App\Models\VehicleModel;
use App\Models\Trim;
use App\Models\MakeMapping;
use App\Models\ModelMapping;
use App\Models\TrimMapping;
use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;
use Illuminate\Support\Collection;
use Override;

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
        $makes = $this->make
            ->join('make_mapping', 'make.id', '=', 'make_mapping.dr_make_id')
            ->where('make_mapping.status', 'active')
            ->distinct()
            ->get(['make.id', 'make.make_name']);

        return $makes;
    }

    public function getModelsByMake(?string $makeCode): Collection
    {
        // Return models from VehicleModel table that are mapped for the given make_id
        $models = $this->model
            ->join('model_mapping', 'model.id', '=', 'model_mapping.dr_model_id')
            ->where('model.make_id', $makeCode)
            ->where('model_mapping.status', 'active')
            ->distinct()
            ->get(['model.id', 'model.model_name']);
        return $models;
    }

    public function getTrimsByModel(?string $modelCode): Collection
    {
        // Return trims from Trim table that are mapped for the given model_id
        $trims = $this->trim
            ->join('trim_mapping', 'trim.id', '=', 'trim_mapping.dr_trim_id')
            ->where('trim.model_id', $modelCode)
            ->where('trim_mapping.status', 'active')
            ->distinct()
            ->get(['trim.id', 'trim.trim_name']);

        return $trims;
    }
}
