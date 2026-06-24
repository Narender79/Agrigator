<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreInsurerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('insurers', 'user_id')->whereNull('deleted_at')],
            'company_name' => ['required', 'string', 'max:255', 'unique:insurers,company_name'],
            'regulatory_reg_no' => ['nullable', 'string', 'max:100', 'unique:insurers,regulatory_reg_no'],
            'license_expiry' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'created_by' => ['nullable', 'integer'],
            'updated_by' => ['nullable', 'integer'],
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

    public function messages(): array
    {
        return [
            'user_id.unique' => 'An insurer already exists for this user.',
            'company_name.unique' => 'An insurer with this company name already exists.',
            'regulatory_reg_no.unique' => 'An insurer with this regulatory registration number already exists.',
            'status.in' => 'The status field must be either active or inactive.',
        ];
    }
}