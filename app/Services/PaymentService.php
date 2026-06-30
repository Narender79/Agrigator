<?php

namespace App\Services;

use App\Models\Insurance\UsersInsurance;
use App\Services\Interfaces\PaymentServiceInterface;
use App\Services\TataAigMasterService;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\UsersInsuranceRepositoryInterface;
// use League\Uri\Contracts\UserInfoInterface;
use App\Utils\DHelper;
use Exception;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(private readonly TataAigMasterService $tataAigService, private readonly UsersInsuranceRepositoryInterface $usersInsuranceRepo) {}

    public function verifyInsurerPayment(UsersInsurance $record): array
    {
        try {
            $payload = [
                'payment_id'           => $record->insurance_payment_id,
                'producer_code'        => config('services.tata_aig.producer_code', '4984727878'),
                'office_location_code' => '122015',
                'office_location_name' => 'Gurugram',
                'policy_start_date'    => $record->policy_start_date ?? now()->format('Y-m-d'),
                'payment_amount'       => (string) round($record->net_premium),
                'pan_no'               => '',
                'payer_type'           => 'Customer',
                'payer_id'             => '',
                'payer_name'           => '',
                'payer_relationship'   => '',
                'consumerAppTransId'   => $record->payment_id,
                'transactionStatus'    => 'Success',
                'gateway_txn_id'       => $record->gateway_txn_id,
                'txn_start_time'       => $record->insurance_txn_start_time
                    ? \Carbon\Carbon::parse($record->insurance_txn_start_time)->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s'),
            ];

            $response = $this->tataAigService->verifyPayment($payload);
            return $response;
        } catch (Exception $e) {
            Log::error("Payment verification failed in PaymentService: " . $e->getMessage(), [
                'insurance_id' => $record->id
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function verifyInsurerPaymentByQuoteId(string $quoteId): array
    {
        $record = $this->usersInsuranceRepo->findByQuoteId($quoteId);
        if (!$record) {
            throw new \App\Exceptions\BusinessException('Insurance record not found for the given Quote ID.', 404);
        }

        if ($record->status !== KYC_COMPLETED) {
            throw new \App\Exceptions\BusinessException('KYC verification must be completed before verifying insurer payment.', 400);
        }

        if (in_array($record->status, [PAYMENT_VERIFIED, POLICY_GENERATED])) {
            return [
                'success' => true,
                'message' => 'Payment is already verified.'
            ];
        }

        $paymentResponse = $this->verifyInsurerPayment($record);

        // If the API call itself failed, throw the actual error message
        if (!$paymentResponse['success']) {
            throw new \App\Exceptions\BusinessException(
                $paymentResponse['message'] ?? 'Payment verification failed at insurer side.',
                400
            );
        }
        if ($paymentResponse['success'] && !empty($paymentResponse['data'])) {
            $paymentData = is_array($paymentResponse['data']) && isset($paymentResponse['data'][0])
                ? $paymentResponse['data'][0]
                : $paymentResponse['data'];

            $updateData = ['status' => PAYMENT_VERIFIED];
            if (!empty($paymentData['policy_no'])) {
                $updateData['policy_no'] = $paymentData['policy_no'];
            }

            if (!empty($paymentData['payment_id'])) {
                $updateData['insuranc_payment_ref_no'] = $paymentData['payment_id'];
            }

            if (!empty($paymentData['encrypted_policy_id'])) {
                $updateData['encrypted_policy_id'] = $paymentData['encrypted_policy_id'];
            }

            if (!empty($paymentData['encrypted_policy_no'])) {
                $updateData['encrypted_policy_no'] = $paymentData['encrypted_policy_no'];
            }
            $this->usersInsuranceRepo->updateOrCreateByUniqueId($record->insurance_unique_id, $updateData);
        }
        return $paymentResponse;
    }


    /**
     * Auth Check: Status must be PAYMENT_VERIFIED or POLICY_GENERATED
     */
    public function downloadPolicyByQuoteId(string $quoteId): array
    {
        $record = $this->usersInsuranceRepo->findByQuoteId($quoteId);
        if (!$record) {
            throw new \App\Exceptions\BusinessException('Insurance record not found for the given Quote ID.', 404);
        }

        // Auth check for Policy Download
        if (!in_array($record->status, [PAYMENT_VERIFIED, POLICY_GENERATED])) {
            throw new \App\Exceptions\BusinessException('Insurer payment must be verified before downloading policy.', 400);
        }


        // 1. Check if local policy PDF file already exists on server disk
        if (!empty($record->policy_no)) {
            $cleanPolicyNo = str_replace(['/', '\\'], '_', $record->policy_no);
            $pdfPath = public_path("photos/policy/policy_{$cleanPolicyNo}.pdf");
            if (file_exists($pdfPath)) {
                return [
                    'success' => true,
                    'message' => 'Policy document retrieved successfully.',
                    'cdn_url' => DHelper::getCdnUrl("photos/policy/policy_{$cleanPolicyNo}.pdf")
                ];
            }
        }
        // 2. Fetch policy document from Tata AIG API
        if (empty($record->encrypted_policy_id)) {
            throw new \App\Exceptions\BusinessException('Encrypted policy ID is missing.', 400);
        }
        $policyRes = $this->tataAigService->downloadPolicy($record->encrypted_policy_id);
        if (!$policyRes['success'] || empty($policyRes['data']['byteStream'])) {
            throw new \App\Exceptions\BusinessException('Policy document is not available yet from insurer.', 400);
        }
        // 3. Save PDF to public disk
        $pdfData = base64_decode($policyRes['data']['byteStream']);
        if ($pdfData === false || empty($pdfData)) {
            throw new \App\Exceptions\BusinessException('Failed to decode the policy document stream.', 500);
        }
        $dirPath = public_path('photos/policy');
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        $cleanPolicyNo = str_replace(['/', '\\'], '_', $record->policy_no ?? $record->quote_id);
        $fileName = 'policy_' . $cleanPolicyNo . '.pdf';
        file_put_contents($dirPath . '/' . $fileName, $pdfData);
        $cdnUrl = DHelper::getCdnUrl('photos/policy/' . $fileName);
        // 4. Update status to POLICY_GENERATED
        $this->usersInsuranceRepo->updateOrCreateByUniqueId($record->insurance_unique_id, [
            'status'     => POLICY_GENERATED,
            'policy_url' => $cdnUrl
        ]);
        return [
            'success' => true,
            'message' => 'Policy document downloaded successfully.',
            'cdn_url' => $cdnUrl
        ];
    }
}
