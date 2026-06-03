<?php

namespace App\Services;

use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;
use Illuminate\Support\Collection;

class VehicleMappingService
{
    protected VehicleMappingRepositoryInterface $mappingRepo;

    public function __construct(VehicleMappingRepositoryInterface $mappingRepo)
    {
        $this->mappingRepo = $mappingRepo;
    }

    public function getFormattedMakes()
    {
        return $this->mappingRepo->getMakes()->map(function ($make) {
            return [
                'id'   => $make->id,
                'name' => $make->make_name,
            ];
        });
    }

    public function getFormattedModels(?string $makeCode): Collection
    {
        return $this->mappingRepo->getModelsByMake($makeCode)->map(function ($model) {
            return [
                'id'   => $model->id,
                'name' => $model->model_name,

            ];
        });
    }

    public function getFormattedTrims(?string $modelCode): Collection
    {
        return $this->mappingRepo->getTrimsByModel($modelCode)->map(function ($trim) {
            return [
                'id'   => $trim->id,
                'name' => $trim->trim_name,
            ];
        });
    }
}
