<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'business_name' => ['required', 'string', 'max:255'],
            'partner_type' => ['sometimes', 'required', 'string', Rule::in(['AGENT', 'DEALER', 'RESELLER'])],
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'contact_person_name' => ['required', 'string', 'max:255'],
            'contact_person_mobile' => ['required', 'string', 'max:15'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['pending', 'active', 'suspended'])],
            'created_by' => ['sometimes', 'nullable', 'integer'],
            'updated_by' => ['sometimes', 'nullable', 'integer'],
        ])->validate();

        $partner = Partner::query()->create([
            'user_id' => $validated['user_id'],
            'business_name' => $validated['business_name'],
            'partner_type' => $validated['partner_type'] ?? 'AGENT',
            'contact_email' => $validated['contact_email'],
            'contact_person_name' => $validated['contact_person_name'],
            'contact_person_mobile' => $validated['contact_person_mobile'],
            'status' => $validated['status'] ?? 'pending',
            'created_by' => $validated['created_by'] ?? null,
            'updated_by' => $validated['updated_by'] ?? null,
        ]);

        return response()->json([
            'message' => 'Partner created successfully.',
            'data' => $this->presentPartner($partner->fresh()),
        ], 201);
    }

    public function update(Request $request, $partner)
    {
        $partner = Partner::query()->find($partner);

        if (! $partner) {
            return response()->json([
                'message' => 'Partner not found.',
            ], 404);
        }

        $validated = Validator::make($request->all(), [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'business_name' => ['sometimes', 'required', 'string', 'max:255'],
            'partner_type' => ['sometimes', 'required', 'string', Rule::in(['AGENT', 'DEALER', 'RESELLER'])],
            'contact_email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
            'contact_person_name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_person_mobile' => ['sometimes', 'required', 'string', 'max:15'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['pending', 'active', 'suspended'])],
            'created_by' => ['sometimes', 'nullable', 'integer'],
            'updated_by' => ['sometimes', 'nullable', 'integer'],
        ])->validate();

        $partner->update([
            'user_id' => array_key_exists('user_id', $validated) ? $validated['user_id'] : $partner->user_id,
            'business_name' => array_key_exists('business_name', $validated) ? $validated['business_name'] : $partner->business_name,
            'partner_type' => array_key_exists('partner_type', $validated) ? $validated['partner_type'] : $partner->partner_type,
            'contact_email' => array_key_exists('contact_email', $validated) ? $validated['contact_email'] : $partner->contact_email,
            'contact_person_name' => array_key_exists('contact_person_name', $validated) ? $validated['contact_person_name'] : $partner->contact_person_name,
            'contact_person_mobile' => array_key_exists('contact_person_mobile', $validated) ? $validated['contact_person_mobile'] : $partner->contact_person_mobile,
            'status' => array_key_exists('status', $validated) ? $validated['status'] : $partner->status,
            'created_by' => array_key_exists('created_by', $validated) ? $validated['created_by'] : $partner->created_by,
            'updated_by' => array_key_exists('updated_by', $validated) ? $validated['updated_by'] : $partner->updated_by,
        ]);

        return response()->json([
            'message' => 'Partner updated successfully.',
            'data' => $this->presentPartner($partner->fresh()),
        ]);
    }

    protected function presentPartner(Partner $partner): array
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

    public function showByUserId($userId)
    {
        $partner = Partner::query()->where('user_id', $userId)->first();

        if (! $partner) {
            return response()->json([
                'code' => 'failed',
                'message' => 'Partner not found.',
            ], 404);
        }

        return response()->json([
            'code' => 'success',
            'data' => $this->presentPartner($partner),
        ]);
    }
}