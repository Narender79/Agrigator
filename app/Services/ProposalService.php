<?php

namespace App\Services;

use App\Models\Insurance\Quote;
use App\Models\Insurance\QuoteReqRes;
use App\Repositories\Interfaces\ProposalRepositoryInterface;
use App\Services\Interfaces\ProposalServiceInterface;
use App\Services\TataAigMasterService;
use App\Models\Insurance\UsersInsurance;
use Exception;
use Override;

class ProposalService implements ProposalServiceInterface
{
    public function __construct(private readonly ProposalRepositoryInterface $proposalRepo, private readonly TataAigMasterService $tataAigService) {}

    public function createProposal(array $payload): array
    {
        try {
            // \Log::info("ok report");

            // 1. Construct Tata AIG Proposal API request body
            $tataAigPayload = [
                'proposer_salutation'          => $payload['salutation'],
                'proposer_fname'               => $payload['first_name'],
                'proposer_mname'               => $payload['middle_name'] ?? '',
                'proposer_lname'               => $payload['last_name'],
                'proposer_email'               => $payload['email'],
                'proposer_mobile'              => $payload['mobile'],
                'proposer_gender'              => $payload['gender'],
                'proposer_marital'             => $payload['marital_status'],
                'proposer_add1'                => $payload['address_line_1'],
                'proposer_occupation'          => strtoupper($payload['occupation']),
                'proposer_pan'                 => $payload['pan'] ?? '',
                'proposer_dob'                 => $payload['dob'],
                'vehicle_puc_declaration'      => 'true',
                'pre_insurer_name'             => $payload['pre_insurer_name'] ?? '',
                'pre_insurer_no'               => $payload['pre_insurer_no'] ?? '',
                'partner_preferred_inspection' => 'TAGIC_AGENCY_INSPECTION',
                'nominee_name'                 => $payload['nominee_name'],
                'nominee_relation'             => $payload['nominee_relation'],
                'nominee_age'                  => (int) $payload['nominee_age'],
                'appointee_name'               => $payload['appointee_name'] ?? '',
                'appointee_relation'           => $payload['appointee_relation'] ?? '',
                'proposal_id'                  => $payload['proposal_id'],
                'product_id'                   => 'M300000000001',
                'declaration'                  => 'Yes',
                'vehicle_chassis'              => $payload['vehicle_chassis'],
                'vehicle_engine'               => $payload['vehicle_engine'],
                'proposer_pincode'             => $payload['pincode'],
                'quote_no'                     => $payload['quote_no'],
                'carriedOutBy'                 => 'No',
                '__finalize'                   => '1',
            ];

            // 4. Send request to insurer
            $result = $this->tataAigService->fetchProposal($tataAigPayload);
            \Log::info($result);
            // die();
            if (!$result['success']) {
                return $result;
            }
            // 5. Store Proposal details in SQL
            $userId = auth()->id() ?? request()->attributes->get('merchant_partner')?->user_id;
            $proposal = $this->proposalRepo->saveProposal([
                'user_id'              => $userId,
                'policy_id'            => $result['data']['policy_id'],
                'quote_id'             => $result['data']['quote_id'],
                'salutation'           => $payload['salutation'],
                'first_name'           => $payload['first_name'],
                'middle_name'          => $payload['middle_name'] ?? null,
                'last_name'            => $payload['last_name'],
                'full_name'            => trim($payload['first_name'] . ' ' . ($payload['middle_name'] ?? '') . ' ' . $payload['last_name']),
                'dob'                  => $payload['dob'],
                'age'                  => $payload['age'] ?? null,
                'gender'               => $payload['gender'],
                'marital_status'       => $payload['marital_status'],
                'nationality'          => $payload['nationality'] ?? 'Indian',
                'occupation'           => $payload['occupation'],
                'email'                => $payload['email'],
                'mobile'               => $payload['mobile'],
                'address_line_1'       => $payload['address_line_1'],
                'address_line_2'       => $payload['address_line_2'] ?? null,
                'pincode'              => $payload['pincode'],
                'nominee_name'         => $payload['nominee_name'],
                'nominee_relation'     => $payload['nominee_relation'],
                'nominee_age'          => $payload['nominee_age'],
                'appointee_name'       => $payload['appointee_name'] ?? null,
                'appointee_relation'   => $payload['appointee_relation'] ?? null,
                'vehicle_chassis'      => $payload['vehicle_chassis'],
                'vehicle_engine'       => $payload['vehicle_engine'],
                'pre_insurer_name'     => $payload['pre_insurer_name'] ?? null,
                'pre_insurer_no'       => $payload['pre_insurer_no'] ?? null,
                'insurer_proposal_ref' => $result['data']['proposal_id'],
                'status'               => $result['data']['proposal_stage'],
            ]);



            // 6. Update the users_insurance record with proposal details for the same session_id
            $proposalStage = $result['data']['proposal_stage'];
            $record = UsersInsurance::where('proposal_id', $payload['proposal_id'])
                ->first();
            if ($record) {
                $sessionId = $record->insurance_unique_id;
            }
            if (!empty($sessionId)) {
                UsersInsurance::updateOrCreate(
                    ['insurance_unique_id' => $sessionId],
                    [
                        'proposal_id'     => $result['data']['proposal_id'] ?? null,
                        'proposal_no'     => $result['data']['proposal_no'] ?? null,
                        'document_id'     => $result['data']['document_id'] ?? null,
                        'policy_id'       => $result['data']['policy_id'] ?? null,
                        'registration_no' => trim(($payload['regno_1'] ?? '') . ($payload['regno_2'] ?? '') . ($payload['regno_3'] ?? '') . ($payload['regno_4'] ?? '')),
                        'city'            => $payload['city'] ?? null,
                        'email'           => $payload['email'] ?? null,
                        'name'            => trim($payload['first_name'] . ' ' . ($payload['middle_name'] ?? '') . ' ' . $payload['last_name']),
                        'phone'           => $payload['mobile'] ?? null,
                        'status'          => 'proposal_' . $proposalStage,
                    ]
                );
            }

            return [
                'success' => true,
                'message' => 'Proposal created successfully.',
                'data'    => [
                    'droom_proposal_id'          => $proposal->id,
                    'insurer_proposal_ref' => $proposal->insurer_proposal_ref,
                    'status'               => $proposal->status,
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create proposal: ' . $e->getMessage()
            ];
        }
    }
}
