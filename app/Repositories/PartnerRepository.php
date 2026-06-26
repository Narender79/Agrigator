<?php

namespace App\Repositories;

use App\Exceptions\BusinessException;
use App\Models\Partner;
use App\Models\PartnerCredential;
use App\Repositories\Interfaces\PartnerRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class PartnerRepository implements PartnerRepositoryInterface
{
    public function __construct(
        private Partner $partnerModel,
        private PartnerCredential $partnerCreModel
    ) {}

    public function createPartner(array $data): array
    {
        $partner = $this->partnerModel->query()->create($this->buildPartnerCreateData($data));

        return $this->presentPartner($partner->fresh());
    }

    private function buildPartnerCreateData(array $data): array
    {
        return [
            'user_id' => $data['user_id'],
            'business_name' => $data['business_name'],
            'partner_type' => $data['partner_type'] ?? 'AGENT',
            'contact_email' => $data['contact_email'],
            'contact_person_name' => $data['contact_person_name'],
            'contact_person_mobile' => $data['contact_person_mobile'],
            'status' => $data['status'] ?? 'pending',
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];
    }

    public function updatePartner(array $data, int $id): array
    {
        $partner = $this->getPartner($id);

        $partner->update($this->buildPartnerUpdateData($data, $partner));

        return $this->presentPartner($partner->fresh());
    }

    private function buildPartnerUpdateData(array $data, Partner $partner): array
    {
        return [
            'user_id' => array_key_exists('user_id', $data) ? $data['user_id'] : $partner->user_id,
            'business_name' => array_key_exists('business_name', $data) ? $data['business_name'] : $partner->business_name,
            'partner_type' => array_key_exists('partner_type', $data) ? $data['partner_type'] : $partner->partner_type,
            'contact_email' => array_key_exists('contact_email', $data) ? $data['contact_email'] : $partner->contact_email,
            'contact_person_name' => array_key_exists('contact_person_name', $data) ? $data['contact_person_name'] : $partner->contact_person_name,
            'contact_person_mobile' => array_key_exists('contact_person_mobile', $data) ? $data['contact_person_mobile'] : $partner->contact_person_mobile,
            'status' => array_key_exists('status', $data) ? $data['status'] : $partner->status,
            'created_by' => array_key_exists('created_by', $data) ? $data['created_by'] : $partner->created_by,
            'updated_by' => array_key_exists('updated_by', $data) ? $data['updated_by'] : $partner->updated_by,
        ];
    }

    public function showPartnerByUserId(int $userId, bool $objReturn = true)
    {
        $partner =  $this->partnerModel->query()->where('user_id', $userId)->first();
        if (!$partner) {
            throw new BusinessException("Partner user not found");
        }
        if ($objReturn) {
            return $partner;
        }
        return $this->presentPartner($partner->fresh());
    }

    public function getPartner(int $id, bool $objReturn = true)
    {
        $partner = $this->partnerModel->findOrFail($id);
        if (!$partner) {
            throw new BusinessException("Partner not found");
        }
        if ($objReturn) {
            return $partner;
        }
        return $this->presentPartner($partner->fresh());
    }

    private function presentPartner(Partner $partner): array
    {
        return [
            'id' => $partner->id,
            'user_id' => $partner->user_id,
            'business_name' => $partner->business_name,
            'partner_type' => $partner->partner_type,
            'contact_email' => $partner->contact_email,
            'contact_person_name' => $partner->contact_person_name,
            'contact_person_mobile' => $partner->contact_person_mobile,
            'status' => $partner->status,
            'created_by' => $partner->created_by,
            'updated_by' => $partner->updated_by,
            'created_at' => $partner->created_at,
            'updated_at' => $partner->updated_at,
        ];
    }

    /** */
    public function createPartnerCredential(array $data): array
    {
        $partnerCred = $this->partnerCreModel->query()->create($this->buildPartnerCredentialCreateData($data));

        return $this->presentCredential($partnerCred->fresh());
    }

    private function buildPartnerCredentialCreateData(array $data): array
    {
        return [
            'partner_id' => $data['partner_id'],
            'api_key' => $data['api_key'],
            'api_secret_hash' => Hash::make($data['api_secret']),
            'status' => $data['status'] ?? 'active',
            'rate_limit_tier' => $data['rate_limit_tier'] ?? 'standard',
        ];
    }

    public function getPartnerCredentialByPartnerId(int $partnerId, bool $objReturn = true)
    {
        $partnerCred =  $this->partnerCreModel->query()->where('partner_id', $partnerId)->first();
        if (!$partnerCred) {
            throw new BusinessException("Partner credential not found");
        }
        if ($objReturn) {
            return $partnerCred;
        }
        return $this->presentCredential($partnerCred->fresh());
    }

    private function presentCredential(PartnerCredential $credential): array
    {
        return [
            'id' => $credential->id,
            'partner_id' => $credential->partner_id,
            'api_key' => $credential->api_key,
            'status' => $credential->status,
            'rate_limit_tier' => $credential->rate_limit_tier,
            'created_at' => $credential->created_at,
            'updated_at' => $credential->updated_at,
        ];
    }
}
