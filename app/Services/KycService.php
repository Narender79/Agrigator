<?php

namespace App\Services;

use App\Services\Interfaces\KycServiceInterface;
use App\Services\TataAigMasterService;
use App\Repositories\Interfaces\UsersInsuranceRepositoryInterface;
use App\Repositories\Interfaces\InsuranceKycVerificationRepositoryInterface;
use \App\Services\Interfaces\PaymentServiceInterface;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Facades\Log;
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
        try {
            $quoteId = $payload['quote_id'];
            // 1. Fetch record from users_insurance using quote_id
            $record = $this->userInsuranceRepo->findByQuoteId($quoteId);

            // 2. Auth check: Ensure proposal is completed
            if (!$record || $record->status !== 'proposal_completed') {
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
                $this->kycVerificationRepo->savekycVerification([
                    'user_id' => $userId,
                    'session_id' => $record->insurance_unique_id,
                    'proposal_no' => $record->proposal_no,
                    'quote_id' => $record->quote_id,
                    'id_type' => 'PAN',
                    'id_number_masked' => $maskedId,
                    'id_number_hash' => $idHash,
                    'customer_name' => $payload['customer_name'],
                    'ckyc_success' => false,
                    'trace_id' => null,
                    'status' => 'failed',
                ]);
                return [
                    'success' => false,
                    'message' => 'Insurer CKYC API connection failed.',
                    'response' => $response['data']
                ];
            }

            // Store the KYC attempt details in database
            $this->kycVerificationRepo->savekycVerification([
                'user_id' => $userId,
                'session_id' => $record->insurance_unique_id,
                'proposal_no' => $record->proposal_no,
                'quote_id' => $record->quote_id,
                'id_type' => 'PAN',
                'id_number_masked' => $maskedId,
                'id_number_hash' => $idHash,
                'customer_name' => $payload['customer_name'],
                'ckyc_success' => true,
                'trace_id' => $response['data']['traceId'] ?? null,
                'status' => 'success',
            ]);

            $this->userInsuranceRepo->updateOrCreateByUniqueId($record->insurance_unique_id, [
                'status' => 'kyc_completed',
            ]);

            // Call the unified payment verification service
            $paymentResponse = $this->paymentService->verifyInsurerPayment($record);
            if (!$paymentResponse['success']) {
                Log::error('Insurer payment verification failed at KYC stage', [
                    'session_id' => $record->insurance_unique_id,
                    'response'   => $paymentResponse
                ]);
            }
            return [
                'success' => true,
                'data' => $response['data'] // return the whole insurer response JSON
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred during KYC verification: ' . $e->getMessage()
            ];
        }
    }
}
