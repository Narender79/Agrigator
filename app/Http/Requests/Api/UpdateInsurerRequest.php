<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateInsurerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $insurerId = $this->route('id');

        return [
            'user_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
                Rule::unique('insurers', 'user_id')
                    ->ignore($insurerId)
                    ->whereNull('deleted_at'),
            ],
            'company_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('insurers', 'company_name')->ignore($insurerId),
            ],
            'regulatory_reg_no' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('insurers', 'regulatory_reg_no')->ignore($insurerId),
            ],
            'license_expiry' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive'])],
            'created_by' => ['sometimes', 'nullable', 'integer'],
            'updated_by' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'An insurer already exists for this user.',
            'company_name.unique' => 'An insurer with this company name already exists.',
            'regulatory_reg_no.unique' => 'An insurer with this regulatory registration number already exists.',
            'status.in' => 'The status field must be either active or inactive.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}