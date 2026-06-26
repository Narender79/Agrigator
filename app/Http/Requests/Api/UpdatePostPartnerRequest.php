<?php

namespace App\Http\Requests\Api;

use App\Utils\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePostPartnerRequest extends FormRequest
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
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'business_name' => ['sometimes', 'required', 'string', 'max:255'],
            'partner_type' => ['sometimes', 'required', 'string', Rule::in(['AGENT', 'DEALER', 'RESELLER'])],
            'contact_email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
            'contact_person_name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_person_mobile' => ['sometimes', 'required', 'string', 'max:15'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['pending', 'active', 'suspended'])],
            'created_by' => ['sometimes', 'nullable', 'integer'],
            'updated_by' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Response::validationFailed($validator->errors()));
    }

    public function messages(): array
    {
        return [
            'partner_type.in' => 'Partner type must be one of: AGENT, DEALER, or RESELLER.'
        ];
    }
}
