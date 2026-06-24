<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInsurerRequest;
use App\Http\Requests\Api\UpdateInsurerRequest;
use App\Models\Insurance\Insurer;
use App\Utils\Response;

class InsurerController extends Controller
{
    public function store(StoreInsurerRequest $request)
    {
        try {
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


            return Response::success(
                $this->presentInsurer($insurer->fresh()),
                array(
                    'message' => 'Insurer created successfully.'
                ),
                201
            );
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }

    public function update(UpdateInsurerRequest $request, Insurer $insurer)
    {
        try {
            $validated = $request->validated();

            $insurer->update([
                'user_id' => array_key_exists('user_id', $validated) ? $validated['user_id'] : $insurer->user_id,
                'company_name' => array_key_exists('company_name', $validated) ? $validated['company_name'] : $insurer->company_name,
                'regulatory_reg_no' => array_key_exists('regulatory_reg_no', $validated) ? $validated['regulatory_reg_no'] : $insurer->regulatory_reg_no,
                'license_expiry' => array_key_exists('license_expiry', $validated) ? $validated['license_expiry'] : $insurer->license_expiry,
                'status' => array_key_exists('status', $validated) ? $validated['status'] : $insurer->status,
                'created_by' => array_key_exists('created_by', $validated) ? $validated['created_by'] : $insurer->created_by,
                'updated_by' => array_key_exists('updated_by', $validated) ? $validated['updated_by'] : $insurer->updated_by,
            ]);

            return Response::success(
                $this->presentInsurer($insurer->fresh()),
                array(
                    'message' => 'Insurer updated successfully.',
                    'validated' => $request->all()
                )
            );
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
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

    public function showByUserId($userId)
    {
        $insurer = Insurer::query()->where('user_id', $userId)->first();

        if (! $insurer) {
            return Response::failed('Insurer not found.', 404, ['message' => 'Insurer not found.'], 404);
        }

        return Response::success($this->presentInsurer($insurer));
    }
}
