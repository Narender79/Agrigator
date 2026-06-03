<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PartnerCredentialController extends Controller
{
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'partner_id' => ['required', 'integer','unique:partner_credentials,partner_id'],
            'api_key' => ['required', 'string', 'max:64', 'unique:partner_credentials,api_key'],
            'api_secret' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive'],
            'rate_limit_tier' => ['sometimes', 'nullable', 'string', 'max:50'],
        ], [
            'partner_id.required' => 'The partner_id field is required.',
            'partner_id.exists' => 'The specified partner does not exist.',
            'api_key.unique' => 'A partner credential already exists for this API key.',
            'status.in' => 'The status field must be either active or inactive.',
        ])->validate();


        $credential = PartnerCredential::query()->create([
            'partner_id' => $validated['partner_id'],
            'api_key' => $validated['api_key'],
            'api_secret_hash' => Hash::make($validated['api_secret']),
            'status' => $validated['status'] ?? 'active',
            'rate_limit_tier' => $validated['rate_limit_tier'] ?? 'standard',
        ]);

        return response()->json([
            'message' => 'Partner credential created successfully.',
            'data' => $this->presentCredential($credential->fresh()),
        ], 201);
    }

    protected function presentCredential(PartnerCredential $credential): array
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

    public function showByPartnerId($partnerId)
    {
        $credential = PartnerCredential::query()->where('partner_id', $partnerId)->first();

        if (! $credential) {
            return response()->json([
                'code' => 'failed',
                'message' => 'Partner credential not found.',
            ], 404);
        }

        return response()->json([
            'code' => 'success',
            'data' => $this->presentCredential($credential),
        ]);
    }
}