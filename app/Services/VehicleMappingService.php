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
                'id'   => $make->insurer_make_id,
                'name' => $make->insurer_make_name,
            ];
        });
    }

    public function getFormattedModels(?string $makeCode): Collection
    {
        return $this->mappingRepo->getModelsByMake($makeCode)->map(function ($model) {
            return [
                'id'   => $model->insurer_model_id,
                'name' => $model->insurer_model_name,

            ];
        });
    }

    public function getFormattedTrims(?string $modelCode): Collection
    {
        return $this->mappingRepo->getTrimsByModel($modelCode)->map(function ($trim) {
            return [
                'id'   => $trim->insurer_trim_id,
                'name' => $trim->insurer_trim_name,
            ];
        });
    }
}
