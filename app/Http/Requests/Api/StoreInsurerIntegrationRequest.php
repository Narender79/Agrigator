<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInsurerIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $otherAttributes = $this->input('other_attributes');

        if (is_string($otherAttributes) && $otherAttributes !== '') {
            $decoded = json_decode($otherAttributes, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge([
                    'other_attributes' => $decoded,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'insurer_id' => [
                'required',
                'integer',
                'exists:insurers,id',
                Rule::unique('insurer_integrations', 'insurer_id')->whereNull('deleted_at'),
            ],
            'api_base_url' => ['required', 'string', 'max:500', 'url'],
            'auth_type' => ['required', 'string', Rule::in(['OAUTH2', 'BASIC_AUTH', 'API_KEY'])],
            'client_id' => ['nullable', 'string', 'max:65535', 'required_if:auth_type,OAUTH2'],
            'client_secret' => ['nullable', 'string', 'max:65535', 'required_if:auth_type,OAUTH2'],
            'environment' => ['required', 'string', 'max:50'],
            'other_attributes' => ['nullable', 'array'],
            'other_attributes.token_url' => ['nullable', 'string', 'url', 'max:500', 'required_if:auth_type,OAUTH2'],
            'other_attributes.scope' => ['nullable', 'string', 'max:1000', 'required_if:auth_type,OAUTH2'],
            'other_attributes.grant_type' => ['nullable', 'string', 'max:100'],
            'other_attributes.cache_key' => ['nullable', 'string', 'max:255'],
            'other_attributes.expiry_buffer_seconds' => ['nullable', 'integer', 'min:1'],
            'other_attributes.timeout' => ['nullable', 'integer', 'min:1'],
            'created_by' => ['nullable', 'integer'],
            'updated_by' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'insurer_id.unique' => 'An insurer integration already exists for this insurer.',
            'client_id.required_if' => 'The client_id field is required when auth_type is OAUTH2.',
            'client_secret.required_if' => 'The client_secret field is required when auth_type is OAUTH2.',
        ];
    }
}