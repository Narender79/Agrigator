<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInsurerIntegrationRequest;
use App\Models\Insurance\InsurerIntegration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

class InsurerIntegrationController extends Controller
{
    public function store(StoreInsurerIntegrationRequest $request)
    {
        \Log::info('Received request to create insurer integration', [
            'request_data' => $request->all(),
        ]);
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