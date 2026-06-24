<?php

namespace App\Services;

use App\Models\Insurance\UsersInsurance;
use App\Services\Interfaces\PaymentServiceInterface;
use App\Services\TataAigMasterService;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(private readonly TataAigMasterService $tataAigService) {}

    public function verifyInsurerPayment(UsersInsurance $record): array
    {
        try {
            $payload = [
                'payment_id'           => $record->insurance_payment_id,
                'producer_code'        => config('services.tata_aig.producer_code', '4984727878'),
                'office_location_code' => '122015',
                'office_location_name' => 'Gurugram',
                'policy_start_date'    => $record->policy_start_date ?? now()->format('Y-m-d'),
                'payment_amount'       => (string) $record->net_premium,
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

            return $this->tataAigService->verifyPayment($payload);
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
}
