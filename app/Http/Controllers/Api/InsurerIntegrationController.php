<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInsurerIntegrationRequest;
use App\Http\Requests\Api\UpdateInsurerIntegrationRequest;
use App\Models\Insurance\InsurerIntegration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

class InsurerIntegrationController extends Controller
{
    public function store(StoreInsurerIntegrationRequest $request)
    {
        $validated = $request->validated();
        $otherAttributes = Arr::get($validated, 'other_attributes', []);

        $integration = InsurerIntegration::query()->create([
            'insurer_id' => $validated['insurer_id'],
            'api_base_url' => $validated['api_base_url'],
            'auth_type' => $validated['auth_type'],
            'client_id' => $this->encryptIfPresent($validated['client_id'] ?? null),
            'client_secret' => $this->encryptIfPresent($validated['client_secret'] ?? null),
            'environment' => $validated['environment'],
            'other_attributes' => $otherAttributes,
            'created_by' => $validated['created_by'] ?? null,
            'updated_by' => $validated['updated_by'] ?? null,
        ]);

        return response()->json([
            'message' => 'Insurer integration created successfully.',
            'data' => $this->presentIntegration($integration->fresh()),
        ], 201);
    }

    public function update(UpdateInsurerIntegrationRequest $request, InsurerIntegration $insurerIntegration)
    {
        $validated = $request->validated();
        $otherAttributes = array_key_exists('other_attributes', $validated)
            ? Arr::get($validated, 'other_attributes', [])
            : $insurerIntegration->other_attributes;

        $insurerIntegration->update([
            'insurer_id' => array_key_exists('insurer_id', $validated) ? $validated['insurer_id'] : $insurerIntegration->insurer_id,
            'api_base_url' => array_key_exists('api_base_url', $validated) ? $validated['api_base_url'] : $insurerIntegration->api_base_url,
            'auth_type' => array_key_exists('auth_type', $validated) ? $validated['auth_type'] : $insurerIntegration->auth_type,
            'client_id' => array_key_exists('client_id', $validated) ? $this->encryptIfPresent($validated['client_id']) : $insurerIntegration->client_id,
            'client_secret' => array_key_exists('client_secret', $validated) ? $this->encryptIfPresent($validated['client_secret']) : $insurerIntegration->client_secret,
            'environment' => array_key_exists('environment', $validated) ? $validated['environment'] : $insurerIntegration->environment,
            'other_attributes' => $otherAttributes,
            'created_by' => array_key_exists('created_by', $validated) ? $validated['created_by'] : $insurerIntegration->created_by,
            'updated_by' => array_key_exists('updated_by', $validated) ? $validated['updated_by'] : $insurerIntegration->updated_by,
        ]);

        return response()->json([
            'message' => 'Insurer integration updated successfully.',
            'data' => $this->presentIntegration($insurerIntegration->fresh()),
        ]);
    }

    protected function encryptIfPresent(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString($value);
    }

    protected function presentIntegration(InsurerIntegration $integration): array
    {
        return [
            'id' => $integration->id,
            'insurer_id' => $integration->insurer_id,
            'api_base_url' => $integration->api_base_url,
            'auth_type' => $integration->auth_type,
            'environment' => $integration->environment,
            'other_attributes' => $integration->other_attributes,
            'created_by' => $integration->created_by,
            'updated_by' => $integration->updated_by,
            'created_at' => $integration->created_at,
            'updated_at' => $integration->updated_at,
        ];
    }
}