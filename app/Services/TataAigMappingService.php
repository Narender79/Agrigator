<?php

namespace App\Services;

use App\Repositories\Contracts\TataAigMappingRepositoryInterface;
use Illuminate\Support\Collection;

class TataAigMappingService
{
    protected TataAigMappingRepositoryInterface $mappingRepo;

    public function __construct(TataAigMappingRepositoryInterface $mappingRepo)
    {
        $this->mappingRepo = $mappingRepo;
    }

    public function getFormattedMakes()
    {
        return $this->mappingRepo->getActiveMakes()->map(function ($mapping) {
            return [
                'insurer_make_id'   => $mapping->insurer_make_id,
                'insurer_make_name' => $mapping->insurer_make_name,
                'dr_make_id'        => $mapping->dr_make_id,
                'dr_make_name'      => $mapping->make?->make_name ?? '—',
            ];
        });
    }

    public function getFormattedModels(?string $makeCode, ?string $makeName): Collection
    {
        return $this->mappingRepo->getActiveModelsByMake($makeCode, $makeName)->map(function ($mapping) {
            return [
                'insurer_model_id'   => $mapping->insurer_model_id,
                'insurer_model_name' => $mapping->insurer_model_name,
                'dr_model_id'        => $mapping->dr_model_id,
                'dr_model_name'      => $mapping->model?->model_name ?? '—',
            ];
        });
    }

    public function getFormattedTrims(?string $modelCode, ?string $modelName): Collection
    {
        return $this->mappingRepo->getActiveTrimsByModel($modelCode, $modelName)->map(function ($mapping) {
            return [
                'insurer_trim_id'   => $mapping->insurer_trim_id,
                'insurer_trim_name' => $mapping->insurer_trim_name,
                'dr_trim_id'        => $mapping->dr_trim_id,
                'dr_trim_name'      => $mapping->trim?->trim_name ?? '—',
            ];
        });
    }
}
