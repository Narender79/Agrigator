<?php

namespace App\Http\Requests\Api;

use App\Utils\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StorePostQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('vehicle_type') === '2w') {
            $this->merge([
                'product_code' => '3187',
            ]);
        } else {
            $this->merge([
                'product_code'      => '3184',
                'pol_plan_variant'  => 'PackagePolicy',
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'make_name'          => 'required|string',
            'model_name'         => 'required|string',
            'trim_name'          => 'required|string',
            'man_year'           => 'required',
            'place_reg'          => 'required|string',
            'proposer_pincode'   => 'required|string',
            'business_type_no'   => 'required|string',
            'dor'                => 'required|date_format:Y-m-d',
            'claim_last'         => 'required|string',
            'pre_pol_ncb'        => 'required|string',
            'reg_no'             => 'required|string',
            '__finalize'         => 'required|string',
            'vehicle_type'       => 'required|string|in:2w,4w',
        ];

        if ($this->input('vehicle_type') === '2w') {
            $rules['pol_tenure'] = 'required|string';
            $rules['plan_type']  = 'required|string';
        } else {
            $rules['pol_plan_id'] = 'required|string';
            $rules['motor_plan_opted_no'] = 'required|string';
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Response::validationFailed($validator->errors()));
    }

     public function messages(): array
    {
        return [
            'vehicle_type.in' => 'Vehicle type must be either 2w or 4w.',
            'dor.date_format' => 'Date of registration must be in Y-m-d format.',
        ];
    }
}
