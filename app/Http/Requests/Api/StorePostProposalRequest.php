<?php

namespace App\Http\Requests\Api;

use App\Utils\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Response::validationFailed($validator->errors()));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'quote_no'            => 'required|string|exists:quotes,id',
            'salutation'          => 'required|string|in:Mr,Mrs,Ms,Dr,M/s',
            'first_name'          => 'required|string|max:100',
            // 'middle_name'         => 'nullable|string|max:100',
            'last_name'           => 'required|string|max:100',
            'dob'                 => 'required|date_format:Y-m-d',
            'gender'              => 'required|string|in:Male,Female,Other',
            'marital_status'      => 'required|string|in:Single,Married,Divorced,Widowed',
            'nationality'         => 'nullable|string|max:50',
            'occupation'          => 'required|string|max:50',
            'email'               => 'required|email|max:150',
            'mobile'              => 'required|string|regex:/^[6-9][0-9]{9}$/',
            'proposal_id'         => 'required|string|min:14',
            'quote_no'            => 'required|string|min:16',
            // Address details
            'address_line_1'      => 'required|string|max:255',
            'address_line_2'      => 'nullable|string|max:255',
            'pincode'             => 'required|string|size:6',
            // Nominee Details
            'nominee_name'        => 'required|string|max:150',
            'nominee_relation'    => 'required|string|max:50',
            'nominee_age'         => 'required|integer|min:1|max:120',
            'appointee_name'      => 'nullable|string|max:150',
            'appointee_relation'  => 'nullable|string|max:50',

            // Vehicle Identity Details
            'vehicle_chassis'     => 'required|string|min:5|max:100',
            'vehicle_engine'      => 'required|string|min:5|max:100',

            // Rollover Policy Details (Optional, depending on business type)
            'pre_insurer_name'    => 'nullable|string|max:150',
            'pre_insurer_no'      => 'nullable|string|max:100',
        ];
    }
    public function messages(): array
    {
        return [
            'marital_status.in' => 'Marital Status must be one of: Single, Married, Divorced, or Widowed.',
            'gender.in' => 'Gender must be one of: Male, Female, or Other.',
            'salutation.in' => 'Salutation must be one of: Mr, Mrs, Ms, Dr, or M/s.',
            'dob.date_format' => 'Date of Birth must be in Y-m-d format.',
        ];
    }
}
