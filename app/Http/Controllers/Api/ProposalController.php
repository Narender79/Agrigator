<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\ProposalServiceInterface;
use App\Utils\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProposalController extends Controller
{
    public function __construct(private readonly ProposalServiceInterface $proposalService) {}

    public function store(Request $request)
    {
        try {
            // Validation rules matching both our DB fields and Tata AIG requirements
            $rules = [
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
                // 'address_line_2'      => 'nullable|string|max:255',
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

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return Response::validationFailed($validator->errors());
            }
            $result = $this->proposalService->createProposal($request->all());
            if (isset($result['success']) && $result['success'] === false) {
                return Response::failed(data_get($result, 'message', 'Proposal creation failed.'), 400, [], 400);
            }
            return Response::success(data_get($result, 'data'));
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }
}
