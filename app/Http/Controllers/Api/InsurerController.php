<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInsurerRequest;
use App\Models\Insurance\Insurer;

class InsurerController extends Controller
{
    public function store(StoreInsurerRequest $request)
    {
        $validated = $request->validated();

        $insurer = Insurer::query()->create([
            'user_id' => $validated['user_id'] ?? null,
            'company_name' => $validated['company_name'],
            'regulatory_reg_no' => $validated['regulatory_reg_no'] ?? null,
            'license_expiry' => $validated['license_expiry'] ?? null,
            'status' => $validated['status'],
            'created_by' => $validated['created_by'] ?? null,
            'updated_by' => $validated['updated_by'] ?? null,
        ]);

        return response()->json([
            'message' => 'Insurer created successfully.',
            'data' => $this->presentInsurer($insurer->fresh()),
        ], 201);
    }

    protected function presentInsurer(Insurer $insurer): array
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