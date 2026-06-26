<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\ProposalRepositoryInterface;
use App\Services\Interfaces\ProposalServiceInterface;
use App\Services\TataAigMasterService;
use App\Repositories\Interfaces\UsersInsuranceRepositoryInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ProposalService implements ProposalServiceInterface
{
    public function __construct(private readonly ProposalRepositoryInterface $proposalRepo, private readonly TataAigMasterService $tataAigService,  private readonly UsersInsuranceRepositoryInterface $usersInsuranceRepo) {}

    public function createProposal(array $payload): array
    {
        try {
            // \Log::info("ok report");
            // Pre check , checking payment status and payment id for proposal genration
            $record = $this->validatePaymentBeforeProposalGeneration($payload);

            // 4. Send request to insurer
            $result = $this->tataAigService->fetchProposal($this->buildTataAigProposalPayload($payload));
            //\Log::info($result);
            // die();
            if (!$result['success']) {
                return $result;
            }
            // 5. Store Proposal details in SQL
            $userId = Auth::id() ?? request()->attributes->get('merchant_partner')?->user_id;

            $proposal = $this->proposalRepo->saveProposal($this->buildProposlCreateData($userId, $result, $payload));

            // 6. Update the users_insurance record with proposal details for the same session_id
            $sessionId = $record->insurance_unique_id;
            if (!empty($sessionId)) {
                $this->usersInsuranceRepo->updateOrCreateByUniqueId($sessionId, $this->buildUserInsuranceUpdateData($result, $payload));
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
                'errors' => 'Failed to create proposal: ' . $e->getMessage()
            ];
        }
    }

    private function validatePaymentBeforeProposalGeneration(array $payload)
    {
        $record = $this->usersInsuranceRepo->findByProposalIdOrQuoteNo($payload['proposal_id'], $payload['quote_no'] ?? null);
        if (!$record || empty($record->droom_payment_id) || $record->status !== 'payment_success') {
            throw new UnauthorizedHttpException(
                '',
                'Proposal generation is unauthorized. Payment has not been completed successfully.'
            );
        }
        return $record;
    }

    private function buildTataAigProposalPayload(array $payload): array
    {
        return [
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
            'inspectionFlag'               => 'false',
            '__finalize'                   => $payload['__finalize'],
        ];
    }

    private function buildProposlCreateData(int $userId, array $result, array $payload): array
    {
        return [
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
            'vehicle_chassis'      => $payload['vehicle_chassis'] ?? null,
            'vehicle_engine'       => $payload['vehicle_engine'],
            'pre_insurer_name'     => $payload['pre_insurer_name'] ?? null,
            'pre_insurer_no'       => $payload['pre_insurer_no'] ?? null,
            'insurer_proposal_ref' => $payload['proposal_id'],
            'status'               => $result['data']['proposal_stage'],
        ];
    }

    private function buildUserInsuranceUpdateData(array $result, array $payload): array
    {
        return [
            'proposal_no'     => $result['data']['proposal_no'] ?? null,
            'nstp_id'         => $result['data']['nstp_id'] ?? null,
            'city'            => $payload['city'] ?? null,
            'email'           => $payload['email'] ?? null,
            'name'            => trim($payload['first_name'] . ' ' . ($payload['middle_name'] ?? '') . ' ' . $payload['last_name']),
            'phone'           => $payload['mobile'] ?? null,
            'status'          => 'proposal_' . $result['data']['proposal_stage'],
            'insurance_payment_id' => $result['data']['payment_id'] ?? null,
        ];
    }
}
