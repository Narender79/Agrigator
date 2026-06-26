<?php

namespace App\Http\Requests\Api;

use App\Utils\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostPartnerCredentialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'integer', 'unique:partner_credentials,partner_id'],
            'api_key' => ['required', 'string', 'max:64', 'unique:partner_credentials,api_key'],
            'api_secret' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive'],
            'rate_limit_tier' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Response::validationFailed($validator->errors()));
    }

    public function messages(): array
    {
        return [
            'partner_id.required' => 'The partner_id field is required.',
            'partner_id.exists' => 'The specified partner does not exist.',
            'api_key.unique' => 'A partner credential already exists for this API key.',
            'status.in' => 'The status field must be either active or inactive.',
        ];
    }
}
