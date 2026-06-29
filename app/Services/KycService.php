<?php

namespace App\Services;

use App\Services\Interfaces\KycServiceInterface;
use App\Services\TataAigMasterService;
use App\Repositories\Interfaces\UsersInsuranceRepositoryInterface;
use App\Repositories\Interfaces\InsuranceKycVerificationRepositoryInterface;
use \App\Services\Interfaces\PaymentServiceInterface;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Facades\Log;
use App\Utils\DHelper;
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
                'status' => KYC_COMPLETED,
            ]);

            // Call the unified payment verification service
            $paymentResponse = $this->paymentService->verifyInsurerPayment($record);
            if (!$paymentResponse['success']) {
                Log::error('Insurer payment verification failed at KYC stage', [
                    'session_id' => $record->insurance_unique_id,
                    'response'   => $paymentResponse
                ]);
                return [
                    'success' => false,
                    'message' => $paymentResponse['message'] ?? 'Insurer payment verification failed.',
                    'data'    => $paymentResponse
                ];
            }

            // \Log::info($paymentResponse);
            // Extract policy details from verification response
            $paymentData = $paymentResponse['data'];
            $updateData = [
                'status' => POLICY_GENERATED,
            ];
            if (!empty($paymentData['policy_no'])) {
                $updateData['policy_no'] = $paymentData['policy_no'];
            }
            if (!empty($paymentData['payment_id'])) {
                $updateData['insuranc_payment_ref_no'] = $paymentData['payment_id'];
            }
            if (!empty($paymentResponse['policy_document']['byteStream'])) {
                $pdfData = base64_decode($paymentResponse['policy_document']['byteStream']);
                $dirPath = public_path('photos/policy');
                if (!file_exists($dirPath)) {
                    mkdir($dirPath, 0777, true);
                }
                $cleanPolicyNo = str_replace(['/', '\\'], '_', $paymentData['policy_no'] ?? $record->quote_id);
                $fileName = 'policy_' . $cleanPolicyNo . '.pdf';
                $filePath = $dirPath . '/' . $fileName;
                file_put_contents($filePath, $pdfData);

                // Construct full dynamic CDN URL via DHelper
                $updateData['policy_url'] = DHelper::getCdnUrl('photos/policy/' . $fileName);
            }

            $this->userInsuranceRepo->updateOrCreateByUniqueId($record->insurance_unique_id, $updateData);
            return [
                'success' => true,
                'data'    => [
                    'ckyc_verification'    => $response['data'],
                    'payment_verification' => $paymentResponse['data'],
                    'policy_document'      => $paymentResponse['policy_document'] ?? null
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred during KYC verification: ' . $e->getMessage()
            ];
        }
    }
}
