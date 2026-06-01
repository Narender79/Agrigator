<?php

namespace App\Repositories\Eloquent;

use App\Models\MakeMapping;
use App\Models\ModelMapping;
use App\Models\TrimMapping;
use App\Repositories\Contracts\TataAigMappingRepositoryInterface;
use Illuminate\Support\Collection;
use Override;

class TataAigMappingRepository implements TataAigMappingRepositoryInterface
{
    protected MakeMapping $makeMapping;
    protected ModelMapping $modelMapping;
    protected TrimMapping $trimMapping;
    public function __construct(
        MakeMapping $makeMapping,
        ModelMapping $modelMapping,
        TrimMapping $trimMapping
    ) {
        $this->makeMapping = $makeMapping;
        $this->modelMapping = $modelMapping;
        $this->trimMapping = $trimMapping;
    }

    #[Override]
    public function getActiveMakes(): Collection
    {
        return $makes = $this->makeMapping->with('make:id,make_name')->where('status', 'active')->get();
    }

    #[Override]
    public function getActiveModelsByMake(?string $makeCode, ?string $makeName): Collection
    {
        // First look up the mapped Droom Make ID from make_mapping
        $makeQuery = $this->makeMapping->where('status', 'active');
        if ($makeCode) {
            $makeQuery->where('insurer_make_id', $makeCode);
        }
        if ($makeName) {
            $makeQuery->where('insurer_make_name', $makeName);
        }

        $makeMapping = $makeQuery->first();
        if (!$makeMapping) {
            return collect();
        }

        return $this->modelMapping->with('model:id,model_name')
            ->where('status', 'active')
            ->where('dr_make_id', $makeMapping->dr_make_id)
            ->get();
    }

    #[Override]
    public function getActiveTrimsByModel(?string $modelCode, ?string $modelName): Collection
    {
        // First look up the mapped Droom Model ID from model_mapping
        $modelQuery = $this->modelMapping->where('status', 'active');
        if ($modelCode) {
            $modelQuery->where('insurer_model_id', $modelCode);
        }
        if ($modelName) {
            $modelQuery->where('insurer_model_name', $modelName);
        }

        $modelMapping = $modelQuery->first();
        if (!$modelMapping) {
            return collect();
        }

        return $this->trimMapping->with('trim:id,trim_name')
            ->where('status', 'active')
            ->where('dr_model_id', $modelMapping->dr_model_id)
            ->get();
    }
}
