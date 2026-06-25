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

    public function getFormattedMakes(?string $vehicleType)
    {
        return $this->mappingRepo->getMakes($vehicleType)->map(function ($make) {
            return [
                // 'id'   => $make->insurer_make_id,
                // 'name' => $make->insurer_make_name,
                'insurer_make_id'   => $make->insurer_make_id,
                'insurer_make_name' => $make->insurer_make_name,
            ];
        });
    }

    public function getFormattedModels(?string $makeCode): Collection
    {
        return $this->mappingRepo->getModelsByMake($makeCode)->map(function ($model) {
            return [
                // 'id'   => $model->insurer_model_id,
                // 'name' => $model->insurer_model_name,
                'insurer_model_id'   => $model->insurer_model_id,
                'insurer_model_name' => $model->insurer_model_name,

            ];
        });
    }

    public function getFormattedTrims(?string $modelCode): Collection
    {
        return $this->mappingRepo->getTrimsByModel($modelCode)->map(function ($trim) {
            return [
                // 'id'   => $trim->insurer_trim_id,
                // 'name' => $trim->insurer_trim_name,
                'insurer_trim_id'   => $trim->insurer_trim_id,
                'insurer_trim_name' => $trim->insurer_trim_name,
            ];
        });
    }
}
