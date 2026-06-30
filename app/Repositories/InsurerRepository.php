<?php

namespace App\Repositories;

use App\Exceptions\BusinessException;
use App\Models\Insurance\Insurer;
use App\Models\Insurance\InsurerIntegration;
use App\Repositories\Interfaces\InsurerRepositoryInterface;

class InsurerRepository implements InsurerRepositoryInterface
{

    public function __construct(
        private InsurerIntegration $insurerIntegrationModel,
        private Insurer $insurerModel
    ) {}

    public function getActiveByName(string $name): ?Insurer
    {
        return $this->insurerModel->where('company_name', $name)
            ->where('status', 'active')
            ->first();
    }

    public function getIntegrationByInsurerId(int $insurerId): ?InsurerIntegration
    {
        return $this->insurerIntegrationModel->where('insurer_id', $insurerId)->first();
    }

    public function createInsurer(array $data): array
    {
        $insurer = $this->insurerModel->query()->create($this->buildInsurerCreateData($data));

        return $this->presentInsurer($insurer->fresh());
    }

    private function buildInsurerCreateData(array $data): array
    {
        return [
            'user_id' => $data['user_id'] ?? null,
            'company_name' => $data['company_name'],
            'regulatory_reg_no' => $data['regulatory_reg_no'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'status' => $data['status'],
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];
    }

    public function updateInsurer(array $data, int $id): array
    {
        $insurer = $this->getInsurer($id);

        $insurer->update($this->buildInsurerUpdateData($data, $insurer));

        return $this->presentInsurer($insurer->fresh());
    }

    private function buildInsurerUpdateData(array $data, Insurer $insurer): array
    {
        return [
            'user_id' => array_key_exists('user_id', $data) ? $data['user_id'] : $insurer->user_id,
            'company_name' => array_key_exists('company_name', $data) ? $data['company_name'] : $insurer->company_name,
            'regulatory_reg_no' => array_key_exists('regulatory_reg_no', $data) ? $data['regulatory_reg_no'] : $insurer->regulatory_reg_no,
            'license_expiry' => array_key_exists('license_expiry', $data) ? $data['license_expiry'] : $insurer->license_expiry,
            'status' => array_key_exists('status', $data) ? $data['status'] : $insurer->status,
            'created_by' => array_key_exists('created_by', $data) ? $data['created_by'] : $insurer->created_by,
            'updated_by' => array_key_exists('updated_by', $data) ? $data['updated_by'] : $insurer->updated_by,
        ];
    }

    public function showInsurerByUserId(int $userId, bool $objReturn = true)
    {
        $insurer =  $this->insurerModel->query()->where('user_id', $userId)->first();
        if (!$insurer) {
            throw new BusinessException("Insurer not found");
        }
        if ($objReturn) {
            return $insurer;
        }
        return $this->presentInsurer($insurer->fresh());
    }

    public function getInsurer(int $id, bool $objReturn = true)
    {
        $insurer = $this->insurerModel->findOrFail($id);

        if (!$insurer) {
            throw new BusinessException("Insurer not found");
        }

        if ($objReturn) {
            return $insurer;
        }
        return $this->presentInsurer($insurer->fresh());
    }

    private function presentInsurer(Insurer $insurer): array
    {
        return [
            'id' => $insurer->id,
            'user_id' => $insurer->user_id,
            'company_name' => $insurer->company_name,
            'regulatory_reg_no' => $insurer->regulatory_reg_no,
            'license_expiry' => $insurer->license_expiry,
            'status' => $insurer->status,
            'created_by' => $insurer->created_by,
            'updated_by' => $insurer->updated_by,
            'created_at' => $insurer->created_at,
            'updated_at' => $insurer->updated_at,
        ];
    }
}
