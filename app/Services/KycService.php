<?php

namespace App\Services;

use App\Services\Interfaces\KycServiceInterface;
use App\Services\TataAigMasterService;
use App\Repositories\Interfaces\UsersInsuranceRepositoryInterface;
use App\Repositories\Interfaces\InsuranceKycVerificationRepositoryInterface;
use \App\Services\Interfaces\PaymentServiceInterface;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\BusinessException;
use Exception;

class KycService implements KycServiceInterface
{
    public function __construct(
        private readonly TataAigMasterService $tataAigService,
        private readonly UsersInsuranceRepositoryInterface $userInsuranceRepo,
        private readonly InsuranceKycVerificationRepositoryInterface $kycVerificationRepo,
        private readonly PaymentServiceInterface $paymentService
    ) {}

    public function verifyKyc(array $payload): array
    {
        $kycProcessStatus = "kyc_failed";
        try {
            $quoteId = $payload['quote_id'];
            // 1. Fetch record from users_insurance using quote_id
            $record = $this->userInsuranceRepo->findByQuoteId($quoteId);

            // 2. Auth check: Ensure proposal is completed
            if (!$record || $record->status !== PROPOSAL_COMPLETED) {
                return [
                    'success' => false,
                    'message' => 'KYC is unauthorized. Proposal has not been completed.'
                ];
            }

            // 3. Prepare Tata AIG payload
            $tataAigPayload = [
                'quote_id' => $quoteId,
                'id_type' => 'PAN', // Fixed to PAN
                'id_num' => strtoupper($payload['id_num']),
                'customer_name' => $payload['customer_name']
            ];

            // 4. Send request to Tata AIG verify API
            $response = $this->tataAigService->verifyCkyc($tataAigPayload);

            // Compute details for database logging
            $userId = Auth::id() ?? request()->attributes->get('merchant_partner')?->user_id;
            $panNo = $payload['id_num'];
            $maskedId = substr($panNo, 0, 2) . str_repeat('X', strlen($panNo) - 4) . substr($panNo, -2);
            $idHash = hash('sha256', $panNo);
            if (!$response['success']) {
                $this->kycVerificationRepo->savekycVerification($this->buildKYCData($userId, $record, $maskedId, $idHash, $payload['customer_name']));
                throw new BusinessException(
                    $response['message'] ?? 'Insurer CKYC API connection failed.',
                    400
                );
            }

            // Store the KYC attempt details in database
            $this->kycVerificationRepo->savekycVerification($this->buildKYCData($userId, $record, $maskedId, $idHash, $payload['customer_name'], true, "success", $response['data']['traceId']));

            $this->userInsuranceRepo->updateOrCreateByUniqueId($record->insurance_unique_id, [
                'status' => KYC_COMPLETED,
            ]);
            $kycProcessStatus = KYC_COMPLETED;

            // 5. Call Payment Verification Service (Sets status to PAYMENT_VERIFIED)
            $paymentResponse = $this->paymentService->verifyInsurerPaymentByQuoteId($record->quote_id);

            $kycProcessStatus = PAYMENT_VERIFIED;
            // 6. Call Policy Download Service (Sets status to POLICY_GENERATED)
            $policyResponse = $this->paymentService->downloadPolicyByQuoteId($record->quote_id);

            return [
                'success' => true,
                'data'    => [
                    'ckyc_verification'    => $response['data'],
                    'payment_verification' => $paymentResponse['data'] ?? null,
                    'policy_document'      => $policyResponse ?? null
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data'    => [
                    'kyc_process_status'    => $kycProcessStatus
                ],
                'errors' => 'An error occurred during KYC verification: ' . $e->getMessage()
            ];
        }
    }

    private function buildKYCData(int $userId, $record, string $maskedId, string $idHash, string $customerName, bool $ckycSuccess = false, string $status = "failed", string $traceId = "")
    {
        return [
            'user_id' => $userId,
            'session_id' => $record->insurance_unique_id,
            'proposal_no' => $record->proposal_no,
            'quote_id' => $record->quote_id,
            'id_type' => 'PAN',
            'id_number_masked' => $maskedId,
            'id_number_hash' => $idHash,
            'customer_name' => $customerName,
            'ckyc_success' => $ckycSuccess,
            'trace_id' => $traceId,
            'status' => $status,
        ];
    }
}
