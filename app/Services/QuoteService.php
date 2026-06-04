<?php

namespace App\Services;

use App\Models\Insurance\Insurer;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Repositories\Interfaces\QuoteRepositoryInterface;
use \App\Models\Insurance\QuoteRequest;
use App\Services\TataAigMasterService;
use App\Models\MakeMapping;
use App\Models\ModelMapping;
use App\Models\TrimMapping;
use Illuminate\Support\Facades\Log;
use Exception;

class QuoteService implements QuoteServiceInterface
{
    protected Insurer $insurer;
    protected MakeMapping $makeMapping;
    protected ModelMapping $modelMapping;
    protected TrimMapping $trimMapping;
    public function __construct(
        private readonly QuoteRepositoryInterface $quoteRepo,
        private readonly TataAigMasterService $tataAigService,
        Insurer $insurer,
        MakeMapping $makeMapping,
        ModelMapping $modelMapping,
        TrimMapping $trimMapping
    ) {
        $this->insurer = $insurer;
        $this->makeMapping = $makeMapping;
        $this->modelMapping = $modelMapping;
        $this->trimMapping = $trimMapping;
    }

    public function getInsurerQuote(string $insurerSlug, array $payload): array
    {
        try {
            // 1. Resolve Insurer via Slug (mapping 'tata-aig' to the database name)
            $companyName = match ($insurerSlug) {
                'tata-aig' => 'TATA AIG GENERAL INSURANCE',
                default => null
            };

            $insurer = $this->insurer->where('company_name', $companyName)->first();

            if (!$insurer || $insurer->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Specified insurer is not active or available.'
                ];
            }

            // 2. Fetch mapping details using Droom IDs
            $makeMapp = $this->makeMapping->where('insurer_id', $insurer->id)
                ->where('dr_make_id', $payload['make_id'] ?? null)
                ->where('status', 'active')
                ->first();
            $modelMapp = $this->modelMapping->where('insurer_id', $insurer->id)
                ->where('dr_model_id', $payload['model_id'] ?? null)
                ->where('status', 'active')
                ->first();
            $trimMapp = $this->trimMapping->where('insurer_id', $insurer->id)
                ->where('dr_trim_id', $payload['trim_id'] ?? null)
                ->where('status', 'active')
                ->first();

            // If no mapping is found for the selected vehicle, return a clear mapping error
            if (!$makeMapp || !$modelMapp || !$trimMapp) {
                return [
                    'success' => false,
                    'message' => 'The selected vehicle details are not mapped for this insurer.'
                ];
            }

            // Generate or capture quote request session ID
            $sessionId = $payload['session_id'] ?? (string) \Illuminate\Support\Str::uuid();
            // Store the quote request session in SQL
            QuoteRequest::firstOrCreate(
                ['session_id' => $sessionId],
                [
                    'vehicle_data' => [
                        'make_id'  => $payload['make_id'],
                        'model_id' => $payload['model_id'],
                        'trim_id'  => $payload['trim_id'],
                        'man_year' => $payload['man_year'] ?? null,
                    ],
                    'status' => 'complete',
                    'insurer_count' => 1,
                    'expires_at' => now()->addDays(7),
                ]
            );

            // 3. Inject carrier-specific nomenclature and codes into the payload
            $payload['vehicle_make']       = $makeMapp->insurer_make_name;
            $payload['vehicle_make_no']    = (int) $makeMapp->insurer_make_id;
            $payload['vehicle_model']      = $modelMapp->insurer_model_name;
            $payload['vehicle_model_no']   = (int) $modelMapp->insurer_model_id;
            $payload['vehicle_variant']    = $trimMapp->insurer_trim_name;
            $payload['vehicle_variant_no'] = (string) $trimMapp->insurer_trim_id;

            // Remove Droom-specific IDs before sending to Tata AIG
            unset($payload['make_id'], $payload['model_id'], $payload['trim_id']);
            // \Log::info($payload);
            // 4. Call Tata AIG Master Service directly
            $result = $this->tataAigService->fetchQuote($payload);

            // 5. Log the quote request & response inside your local database via the Repository
            $price = $result['success'] ? ($result['data']['premium_details']['total_premium'] ?? 0) : 0;

            $this->quoteRepo->saveQuote([
                'insurer_id'               => $insurer->id,
                'proposer_type'            => $payload['proposer_type'] ?? 'Individual',
                'proposer_pincode'         => $payload['proposer_pincode'] ?? '',
                'vehicle_make'             => $payload['vehicle_make'] ?? '',
                'vehicle_model'            => $payload['vehicle_model'] ?? '',
                'vehicle_variant'          => $payload['vehicle_variant'] ?? '',
                'man_year'                 => $payload['man_year'] ?? null,
                'price_offered'            => $price,
                'request_payload'          => $payload,
                'insurer_response_payload' => $result,
                'status'                   => $result['success'] ? 'success' : 'failed',
            ]);

            return $result;
        } catch (Exception $e) {
            // Log::error('Error fetching quote in QuoteService: ' . $e->getMessage(), [
            //     'payload' => $payload,
            //     'exception' => $e
            // ]);
            return [
                'success' => false,
                'message' => 'An error occurred while fetching insurer quote: ' . $e->getMessage()
            ];
        }
    }
}
