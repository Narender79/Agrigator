<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationExceptions;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Services\TataAigMasterService;
use App\Repositories\Interfaces\InsurerRepositoryInterface;
use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;
use App\Models\Insurance\UsersInsurance;
use \App\Utils\Logger;

class QuoteService implements QuoteServiceInterface
{
    private array $booleanKeys = [
        'rtn_invoice',
        'cng_lpg',
        'dep_reimburse',
        'return_invoice',
        'consumbale_expense',
        'rsa',
        'add_towing',
        'own_premises',
        'automobile_association_cover',
        'antitheft_cover',
        'vehicle_blind',
        'driving_tution',
        'tppd_discount',
        'voluntary_deductibles',
        'pa_paid',
        'pa_unnamed',
        'll_paid',
        'll_emp',
        'add_tppd',
        'add_pa_owner',
        'add_pa_unnamed',
        'vehicle_trails_racing',
        'ext_racing',
        'imported_veh_without_cus_duty',
        'fibre_fuel_tank',
        'loss_accessories',
        'geography_extension',
        'side_car',
        'non_electrical_acc',
        'electrical_acc',
        'emg_med_exp',
        'geography_extension_bang',
        'geography_extension_bhutan',
        'geography_extension_lanka',
        'geography_extension_maldives',
        'geography_extension_nepal',
        'geography_extension_pak'
    ];
    protected InsurerRepositoryInterface $insurerRepo;
    protected VehicleMappingRepositoryInterface $vehicleMappingRepo;
    public function __construct(
        private readonly QuoteRepositoryInterface $quoteRepo,
        private readonly TataAigMasterService $tataAigService,
        InsurerRepositoryInterface $insurerRepo,
        VehicleMappingRepositoryInterface $vehicleMappingRepo,
        private UsersInsurance $UsersInsuranceModel
    ) {
        $this->insurerRepo = $insurerRepo;
        $this->vehicleMappingRepo = $vehicleMappingRepo;
    }

    public function getInsurerQuote(array $payload): array
    {

        // checking does this vehical have policy or not on our end
        $this->checkActivePolicy($payload['reg_no'] ?? null);

        // 1. Resolve Insurer
        $insurer = $this->insurerRepo->getActiveByName('TATA AIG GENERAL INSURANCE');

        if (!$insurer || $insurer->status !== 'active') {
            throw new BusinessException("Insurer is not active or available", 500);
        }

        list($makeCode, $modelCode, $variantCode) = $this->validateVehicleCodeMapping($payload);
        list($makeName, $modelName, $variantName) = $this->validateVehicleNameMapping($payload);

        // Inject carrier-specific nomenclature and codes into the payload
        $payload['vehicle_make']       = $makeName;
        $payload['vehicle_model']      = $modelName;
        $payload['vehicle_variant']    = $variantName;

        $vehicleType = $payload['vehicle_type'] ?? '4w';
        $payload['product_code'] = $vehicleType === '2w' ? '3187' : '3184';

        $isTwoWheeler = ($payload['product_code'] === '3187');

        if ($isTwoWheeler) {
            $payload['make_code']    = (string) $makeCode;
            $payload['model_code']   = (string) $modelCode;
            $payload['variant_code'] = (string) $variantCode;
            if (isset($payload['man_year'])) {
                $payload['man_year'] = (string) $payload['man_year'];
            }
        } else {
            $payload['vehicle_make_no']    = (int) $makeCode;
            $payload['vehicle_model_no']   = (int) $modelCode;
            $payload['vehicle_variant_no'] = (string) $variantCode;
            if (isset($payload['man_year'])) {
                $payload['man_year'] = (int) $payload['man_year'];
            }
        }

        // Generate or capture quote request session ID
        $sessionId = $payload['session_id'] ?? (string) \Illuminate\Support\Str::uuid();
        $payload['session_id'] = $sessionId;
        // Store the quote request session in SQL
        $this->quoteRepo->firstOrCreateQuoteRequest($sessionId, [
            'vehicle_data' => [
                'make_name'  => $makeName,
                'model_name' => $modelName,
                'trim_name'  => $variantName,
                'man_year' => $payload['man_year'] ?? null,
            ],
            'status' => 'complete',
            'insurer_count' => 1,
            'expires_at' => now()->addDays(7),
        ]);

        // Map business_type from business_type_no if not present
        if (empty($payload['business_type']) && !empty($payload['business_type_no'])) {
            $payload['business_type'] = $payload['business_type_no'] === '01' ? 'New Business' : 'Roll Over';
        }

        // Parse full registration number into four parts
        $this->prepareRegistrationPayload($payload);

        // Default values  
        $payload['q_producer_code'] = $payload['q_producer_code'] ?? '4984727878';
        $payload['product_id'] = $payload['product_id'] ?? 'M300000000001';
        $payload['pol_start_date'] = $payload['pol_start_date'] ?? now()->format('Y-m-d');
        $payload['proposer_type'] = $payload['proposer_type'] ?? 'Individual';
        $payload['no_past_pol'] = $payload['no_past_pol'] ?? 'N';

        $this->buildQuotePayloadBaseOnVechileType($payload, $isTwoWheeler);

        // Schema-required missing properties
        $this->setDefaultPayloadValues($payload);

        // 4. Call Tata AIG Master Service directly
        $response = $this->tataAigService->fetchQuote($payload);
        $status = $response['success'] ? 'success' : 'failed';
        if ($status == 'failed') {
            $errorMessage = $response['message'] ?? 'Failed to retrieve quote from Tata AIG.';
            if (isset($response['errors']['message_txt'])) {
                $errorMessage .= ' - ' . $response['errors']['message_txt'];
            }

            // Write to insure_error.log and insure_detail_error.log
            Logger::logException($errorMessage, 400, [
                'payload' => $payload,
                'response' => $response
            ]);
            throw new BusinessException($response['errors']['message_txt'] ?? $errorMessage, 400);
        }

        $result = $response['data'];


        // 5. Log the quote request & response inside your local database via the Repository
        $price = $result['data']['premium_break_up']['final_premium'];

        $this->quoteRepo->saveQuote([
            'session_id'               => $sessionId,
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
            'status'                   => $status,
        ]);

        // Check the category_id from the model mapping to determine 4w vs 2w dynamically
        $vehicleType = $payload['vehicle_type'] ?? '4w';
        $result['available_options'] = $this->availableOptions($vehicleType);
        $result['session_id'] = $sessionId;
        return $result;
    }

    private function validateVehicleCodeMapping(array $payload): array
    {
        $makeCode    = $payload['insurer_make_id'] ?? null;
        $modelCode   = $payload['insurer_model_id'] ?? null;
        $variantCode = $payload['insurer_trim_id'] ?? null;
        // If the mapping IDs are missing or null, reject the request
        if (empty($makeCode) || empty($modelCode) || empty($variantCode)) {
            $msg = 'The selected vehicle details are not mapped for this insurer.';
            Logger::logException($msg, 400, [
                'payload' => $payload,
                'make_code' => $makeCode,
                'model_code' => $modelCode,
                'variant_code' => $variantCode,
            ]);
            throw new BusinessException($msg, 400);
        }
        return [$makeCode, $modelCode, $variantCode];
    }
    private function validateVehicleNameMapping(array $payload): array
    {
        $makeName    = $payload['insurer_make_name'] ?? null;
        $modelName   = $payload['insurer_model_name'] ?? null;
        $variantName = $payload['insurer_trim_name'] ?? null;

        // If the mapping names are missing or null, reject the request
        if (empty($makeName) || empty($modelName) || empty($variantName)) {
            $msg = 'The selected vehicle details are not name mapped for this insurer';
            Logger::logException($msg, 400, [
                'payload' => $payload,
                'make_name' => $makeName,
                'model_name' => $modelName,
                'variant_name' => $variantName,
            ]);
            throw new BusinessException($msg, 400);
        }
        return [$makeName, $modelName, $variantName];
    }

    private function prepareRegistrationPayload(array &$payload)
    {
        // Parse full registration number into four parts
        $regNo = strtoupper(str_replace([' ', '-'], '', $payload['reg_no'] ?? ''));

        $payload['regno_1'] = '';
        $payload['regno_2'] = '';
        $payload['regno_3'] = '';
        $payload['regno_4'] = '';

        //Example: HR98W9712 -> regno_1: HR, regno_2: 98, regno_3: W, regno_4: 9712
        if (preg_match('/^([A-Z]{2})([0-9]{1,2})([A-Z]{0,2})([0-9]{1,4})$/i', $regNo, $matches)) {

            $payload['regno_1'] = $matches[1];
            $payload['regno_2'] = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $payload['regno_3'] = $matches[3];
            $payload['regno_4'] = $matches[4];
        }

        // Resolve place_reg_no (RTO code) dynamically based on registration prefix
        if (
            empty($payload['place_reg_no']) &&
            !empty($payload['regno_1']) &&
            !empty($payload['regno_2'])
        ) {

            $rtoResponse = $this->tataAigService->fetchRto();

            if (
                isset($rtoResponse['status']) &&
                $rtoResponse['status'] === 200 &&
                !empty($rtoResponse['data'])
            ) {
                foreach ($rtoResponse['data'] as $item) {

                    if (
                        strcasecmp($item['code1'], $payload['regno_1']) === 0 &&
                        strcasecmp($item['code2'], $payload['regno_2']) === 0
                    ) {
                        $payload['place_reg_no'] = (string) $item['code'];
                        break;
                    }
                }
            }
        }

        // Fallback: If place_reg_no is present but place_reg is empty
        if (empty($payload['place_reg']) && !empty($payload['place_reg_no'])) {
            $rtoResponse = $this->tataAigService->fetchRto();
            if (
                isset($rtoResponse['status']) &&
                $rtoResponse['status'] === 200 &&
                !empty($rtoResponse['data'])
            ) {
                foreach ($rtoResponse['data'] as $item) {
                    if ((string) $item['code'] === (string) $payload['place_reg_no']) {
                        $payload['place_reg'] = (string) $item['name'];
                        break;
                    }
                }
            }
        }
    }

    private function buildQuotePayloadBaseOnVechileType(array &$payload, bool $isTwoWheeler): void
    {
        if (!$isTwoWheeler) {
            // --- FOUR WHEELER (4W) POLICY LOGIC & DEFAULTS ---
            $this->buildFourWheelerPayloads($payload);
        } else {
            // --- TWO WHEELER (2W) POLICY LOGIC & DEFAULTS ---
            $this->buildTwoWheelerPayloads($payload);
        }
    }
    private function buildTwoWheelerPayloads(array &$payload): void
    {
        $this->calculateBundleTpDetails($payload, '2w');

        // 1. Map and format PA Owner Cover
        if ($this->isTruthy($payload['pa_owner'] ?? '')) {
            $payload['pa_owner'] = 'true';
            $payload['pa_owner_tenure'] = $payload['pa_owner_tenure'] ?? '1';
            $payload['cpa_ten'] = $payload['pa_owner_tenure'];
            $payload['cpa_start_date'] = $payload['pol_start_date'];
            $polStart = new \DateTime($payload['pol_start_date']);
            $cpaEnd = clone $polStart;
            $cpaEnd->modify('+' . $payload['cpa_ten'] . ' years -1 day');
            $payload['cpa_end_date'] = $cpaEnd->format('Y-m-d');
        } else {
            $payload['pa_owner'] = 'false';
            $payload['pa_owner_tenure'] = '';
            $payload['cpa_ten'] = '0';
            $payload['cpa_start_date'] = '';
            $payload['cpa_end_date'] = '';
        }

        // 2. Previous Policy properties mapping
        $payload['prev_pol_type'] = $payload['prev_pol_type'] ?? 'PackagePolicy';

        if (empty($payload['prev_pol_start_date'])) {
            $dates = $this->calculatePreviousPolicyDates($payload['pol_start_date']);
            $payload['prev_pol_start_date'] = $dates['prev_pol_start_date'];
            $payload['prev_pol_end_date']   = $dates['prev_pol_end_date'];
        }

        // 3. Default other covers required by the 2W API schema to 'false'
        $booleanKeys = ['special_regno'];
        $allkeys = $this->getAllBooleanKeys($booleanKeys);
        foreach ($allkeys as $key) {
            $payload[$key] = $payload[$key] ?? 'false';
        }

        // 4. Force 2W product details
        $payload['product_id'] = 'M300000000002';
        $payload['product_name'] = 'Two Wheeler';

        // 5. Schema Type Sanitization (cast to strings/proper booleans for UAT API validation)
        if (isset($payload['pre_pol_ncb'])) {
            $payload['pre_pol_ncb'] = (string) $payload['pre_pol_ncb'];
        }
        if (isset($payload['pol_tenure'])) {
            $payload['pol_tenure'] = (string) $payload['pol_tenure'];
        }
        if (isset($payload['__finalize'])) {
            $payload['__finalize'] = (string) $payload['__finalize'];
        }
        if (isset($payload['no_past_pol'])) {
            $payload['no_past_pol'] = $this->isTruthy($payload['no_past_pol']) ? 'true' : 'false';
        }
        if (isset($payload['claim_last'])) {
            $payload['claim_last'] = $this->isTruthy($payload['claim_last']) ? 'true' : 'false';
        }

        $payload['dep_reimb'] = $payload['dep_reimb'] ?? 'true';
        $payload['no_past_pol'] = $payload['no_past_pol'] ?? 'false';

        $this->initializeStandardCovers($payload, '2w');
    }

    private function buildFourWheelerPayloads(array &$payload): void
    {
        $this->calculateBundleTpDetails($payload, '4w');

        if (isset($payload['pol_plan_id'])) {
            $polPlanId = $payload['pol_plan_id'];
            // 1. Map the pol_plan_variant string
            if (in_array($polPlanId, ['01', '03'])) {
                $payload['pol_plan_variant'] = 'Standalone TP';
                $payload['prev_pol_type'] = 'Liability';
            } elseif (in_array($polPlanId, ['02', '04'])) {
                $payload['pol_plan_variant'] = 'PackagePolicy';
                $payload['prev_pol_type'] = 'Package';
            } elseif ($polPlanId === '05') {
                $payload['pol_plan_variant'] = 'Standalone OD';
                $payload['prev_pol_type'] = 'Liability';
            }

            // 2. Adjust dates and fields based on Plan Type
            if (in_array($polPlanId, ['01', '03'])) {
                $payload['pol_od_end_date'] = '';
                $payload['motor_plan_opted_no'] = '';
                $payload['dep_reimburse_claims'] = '';
                $payload['engine_secure_options'] = '';
                $payload['tyre_secure_options'] = '';
            } else if (in_array($polPlanId, ['02', '04', '05'])) {
                $polStart = new \DateTime($payload['pol_start_date']);
                $odEnd = clone $polStart;
                $odEnd->modify('+1 year -1 day');
                $payload['pol_od_end_date'] = $odEnd->format('Y-m-d');

                if ($polPlanId === '04') {
                    $payload['ble_tp_start'] = $payload['pol_start_date'];
                    $tpEnd = clone $polStart;
                    $tpEnd->modify('+3 years -1 day');
                    $payload['ble_tp_end'] = $tpEnd->format('Y-m-d');
                } else if ($polPlanId == '05') {
                    $dates = $this->calculatePreviousPolicyDates($payload['pol_start_date']);
                    $payload['prev_pol_start_date'] = $dates['prev_pol_start_date'];
                    $payload['prev_pol_end_date']   = $dates['prev_pol_end_date'];
                }
            }

            // 3. Handle PA Owner Cover
            if ($polPlanId === '05') {
                $payload['pa_owner'] = 'No';
                $payload['pa_owner_tenure'] = '';
            } else {
                if ($this->isTruthy($payload['pa_owner'] ?? '')) {
                    $payload['pa_owner'] = 'Yes';
                    $payload['pa_owner_tenure'] = $payload['pa_owner_tenure'] ?? '1';
                } else {
                    $payload['pa_owner'] = 'No';
                    $payload['pa_owner_tenure'] = '';
                }
            }

            // 4. Map add-ons based on motor_plan_opted_no (if not Standalone TP)
            if (!in_array($polPlanId, ['01', '03'])) {
                $payload['motor_plan_opted_no'] = $payload['motor_plan_opted_no'] ?? 'P1';
                $motorPlan = $payload['motor_plan_opted_no'];
                if (in_array($motorPlan, ['P4', 'P6', 'P7', 'P11', 'P12'])) {
                    $payload['engine_secure_options'] = 'WITHOUT DEDUCTIBLE';
                }
                if (in_array($motorPlan, ['P5', 'P6', 'P7'])) {
                    $payload['tyre_secure_options'] = 'REPLACEMENT BASIS';
                }
                if (in_array($motorPlan, ['P3', 'P4', 'P5', 'P6', 'P7', 'P10', 'P11', 'P12'])) {
                    $payload['dep_reimburse_claims'] = '2';
                }
            } else {
                $payload['motor_plan_opted_no'] = '';
                $payload['engine_secure_options'] = '';
                $payload['tyre_secure_options'] = '';
                $payload['dep_reimburse_claims'] = '';
            }
        }

        // 5. Default 4W-specific properties and covers
        $payload['product_name'] = $payload['product_name'] ?? 'Private Car';
        $booleanNoKeys = [
            'tyre_secure',
            'engine_secure',
            'key_replace',
            'repair_glass',
            'personal_loss',
            'emergency_expense',
            'prev_tyre',
            'prev_engine',
            'prev_dep',
            'prev_consumable'
        ];
        $allkeys = $this->getAllBooleanKeys($booleanNoKeys);
        foreach ($allkeys as $key) {
            $payload[$key] = $payload[$key] ?? 'No';
        }

        $payload['special_regno'] = $payload['special_regno'] ?? 'false';

        $this->initializeStandardCovers($payload, '4w');
    }

    private function setDefaultPayloadValues(array &$payload): void
    {
        $defaults = [
            '__finalize' => 0,
            'pa_owner_declaration' => 'None',
            'pa_named' => '',
            'prev_cnglpg' => 'false',
            'pa_paid_no' => '',
            'pa_paid_si' => '',
            'pa_unnamed_no' => '',
            'pa_unnamed_si' => '',
            'add_towing_amount' => '',
            'll_paid_no' => '',
            'll_emp_no' => '',
            'fleet_policy' => 'false',
            'fleet_code' => '',
            'fleet_name' => '',
            'vehicle_idv' => '',
            'quote_id' => '',
            'side_car_idv' => '',
            'non_electrical_si' => '',
            'electrical_si' => '',
            'voluntary_deductibles_amt' => '',
            'dep_reimburse_claims' => '',
            'dep_reimburse_deductible' => '',
            'driver_age' => '',
            'driver_gender' => '',
            'driver_occupation' => '',
            'claim_last_count' => '',
            'claim_last_amount' => '',
            'veh_plying_city' => '',
            'non_electrical_des' => '',
            'electrical_des' => '',
            'emg_med_exp_si' => '',
            'add_tppd_si' => '',
            'add_pa_owner_si' => '',
            'add_pa_unnamed_si' => '',
            'event_name' => '',
            'promoter_name' => '',
            'event_from_date' => '',
            'event_to_date' => '',
            'loss_accessories_idv' => '',
            'q_producer_email' => 'pi_uat@tataaig.com',
            'uw_loading' => '',
            'uw_discount' => '',
            'voluntary_amount' => '',
            'cng_lpg_si' => '',
            'automobile_association_mem_exp_date' => '',
            'automobile_association_mem_no' => '',
        ];

        foreach ($defaults as $key => $value) {
            $payload[$key] ??= $value;
        }
    }

    private function availableOptions(string $vehicleType): array
    {
        return [
            'policy_plans' => config("insurance_masters.policy_plans.{$vehicleType}", []),
            'motor_plans'  => config("insurance_masters.motor_plans.{$vehicleType}", []),
            'add_ons'      => config("insurance_masters.add_ons.{$vehicleType}", []),
            'business_types'            => config('insurance_masters.business_types'),
            'prev_policy_types'         => config('insurance_masters.prev_policy_types'),
            'prev_policy_ncbs'          => config('insurance_masters.prev_policy_ncbs'),
            'voluntary_amounts'         => config('insurance_masters.voluntary_amounts'),
            'nominee_relationships'     => config('insurance_masters.nominee_relationships'),
            'appointee_relationships'   => config('insurance_masters.appointee_relationships'),
            'financier_types'           => config('insurance_masters.financier_types'),
            // 'tyre_secure_options'       => config('insurance_masters.tyre_secure_options'),
            // 'engine_secure_options'     => config('insurance_masters.engine_secure_options'),
            'owner_driver_declarations' => config('insurance_masters.owner_driver_declarations'),
            'occupations'               => config('insurance_masters.occupations'),
        ];
    }
    public function getAllBooleanKeys(array $boolKeys): array
    {
        return array_merge($this->booleanKeys, $boolKeys);
    }

    public function updateQuotePaymentStatus(array $payload): array
    {
        if (
            empty($payload['quote_id']) ||
            empty($payload['droom_payment_id']) ||
            empty($payload['gateway_txn_id']) ||
            empty($payload['payment_status']) ||
            empty($payload['payment_id']) ||
            empty($payload['selling_price'])
        ) {
            throw new ValidationExceptions('Something missing in your payload.');
        }
        $getInsuranceObj = $this->quoteRepo->checkValidQuoteID($payload['quote_id']);
        if ($getInsuranceObj->status != QUOTE_COMPLETED) {
            throw new BusinessException(
                'Your quotation is not finalized.',
                400
            );
        }

        // Check Payable Amount
        if ($getInsuranceObj->net_premium != $payload['selling_price']) {
            throw new BusinessException(
                'Payable amount mismatch.',
                400
            );
        }

        $updateData = $this->quoteRepo->updatePaymentStatus($payload);
        if ($updateData->status != PAYMENT_SUCCESS) {
            throw new BusinessException(
                'Payment status not updated. Please try again.',
                500
            );
        }
        return [
            'success' => true,
            'data' => "Quote payment updated successfully."
        ];
    }

    /**
     * Check if the vehicle already has an active policy generated in the system or externally.
     *
     * @param string|null $regNo
     * @throws BusinessException
     * @return void
     */
    private function checkActivePolicy(?string $regNo): Void
    {
        $formattedRegNo = strtoupper(str_replace([' ', '-'], '', $regNo ?? ''));
        // Skip check for empty or temporary/new registration numbers
        if (empty($formattedRegNo)) {
            return;
        }

        // 1. Check database for policy generated on our end
        $existingPolicy = $this->UsersInsuranceModel->where('registration_no', $formattedRegNo)
            ->whereNotNull('policy_no')
            ->where('policy_no', '!=', '')
            ->first();

        if ($existingPolicy) {
            $startDate = \Carbon\Carbon::parse($existingPolicy->policy_start_date);
            $expirationDate = $startDate->copy()->addYear();

            if (now()->lessThan($expirationDate)) {
                $daysRemaining = now()->diffInDays($expirationDate);
                if ($daysRemaining > 90) {
                    throw new BusinessException(
                        "An active policy ({$existingPolicy->policy_no}) already exists for this vehicle with {$daysRemaining} days remaining. Renewal is only allowed within 90 days of expiration.",
                        400
                    );
                }
            }
        }

        // 2. Check vehicle RC history for external policy validity
        $history = app(\App\Models\VehicleHistory::class)
            ->where('rc_number', strtolower($formattedRegNo))
            ->latest('_id')
            ->first();

        if ($history && !empty($history->response['insurance_upto'])) {
            $insuranceUptoStr = $history->response['insurance_upto'];
            if (strtoupper(trim($insuranceUptoStr)) !== 'NA') {
                $expirationDate = null;
                try {
                    $expirationDate = \Carbon\Carbon::parse($insuranceUptoStr);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed parsing vehicle history insurance expiry date: {$insuranceUptoStr}");
                }

                if ($expirationDate && now()->lessThan($expirationDate)) {
                    $daysRemaining = now()->diffInDays($expirationDate);
                    if ($daysRemaining > 90) {
                        throw new BusinessException(
                            "An active policy (expires on {$insuranceUptoStr}) already exists for this vehicle with {$daysRemaining} days remaining. Renewal is only allowed within 90 days of expiration.",
                            400
                        );
                    }
                }
            }
        }
    }

    /**
     * Calculate default start and end dates for the previous policy (exactly 1 year prior).
     */
    private function calculatePreviousPolicyDates(string $polStartDate): array
    {
        $polStart = new \DateTime($polStartDate);
        $prevEnd = clone $polStart;
        $prevEnd->modify('-1 day');
        $prevStart = clone $prevEnd;
        $prevStart->modify('-1 year +1 day');

        return [
            'prev_pol_start_date' => $prevStart->format('Y-m-d'),
            'prev_pol_end_date'   => $prevEnd->format('Y-m-d')
        ];
    }

    /**
     * Determine if a value is semantically true.
     */
    private function isTruthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['true', 'yes', '1', 'y']);
    }

    /**
     * Initialize standard cover defaults based on vehicle type.
     */
    private function initializeStandardCovers(array &$payload, string $vehicleType): void
    {
        $defaultValue = ($vehicleType === '2w') ? 'false' : 'No';

        $covers = [
            'cng_lpg_cover',
            'vintage_car',
            'ncb_protection',
            'load_fibre',
            'load_imported',
            'load_tuition'
        ];

        foreach ($covers as $cover) {
            $payload[$cover] = $payload[$cover] ?? (($cover === 'cng_lpg_cover' && $vehicleType === '4w') ? 'NO' : $defaultValue);
        }
    }

    /**
     * Automatically calculate and populate bundle TP details for Standalone OD policies.
     */
    private function calculateBundleTpDetails(array &$payload, string $vehicleType): void
    {
        $isStandaloneOd = false;
        if ($vehicleType === '2w') {
            $isStandaloneOd = (strpos($payload['plan_type'] ?? '', 'Standalone OD') !== false);
        } else {
            $isStandaloneOd = (($payload['pol_plan_id'] ?? '') === '05');
        }

        if (!$isStandaloneOd) {
            return;
        }

        // 1. Calculate TP start and end dates based on Registration Date (dor)
        if (!empty($payload['dor'])) {
            $dorDate = null;
            try {
                $dorDate = \Carbon\Carbon::parse($payload['dor']);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to parse registration date (dor) for Standalone OD bundle TP calculation: " . ($payload['dor'] ?? ''));
            }

            if ($dorDate) {
                $yearsLimit = ($vehicleType === '2w') ? 5 : 3;
                $bundleTpEndDate = $dorDate->copy()->addYears($yearsLimit)->subDay();
                $newPolicyEndDate = \Carbon\Carbon::parse($payload['pol_start_date'] ?? now()->format('Y-m-d'))->copy()->addYear()->subDay();

                if ($newPolicyEndDate->greaterThan($bundleTpEndDate)) {
                    $isFinalize = isset($payload['__finalize']) && $this->isTruthy($payload['__finalize']);

                    if ($isFinalize) {
                        // Throw the error only during finalization stage
                        throw new BusinessException(
                            "Standalone Own Damage (OD) plan is not allowed because the new policy end date ({$newPolicyEndDate->format('Y-m-d')}) exceeds the vehicle's bundle Third Party (TP) end date ({$bundleTpEndDate->format('Y-m-d')}). Please opt for a Package Policy instead.",
                            400
                        );
                    } else {
                        // Fallback to Package Policy during the quoting stage (non-finalize)
                        if ($vehicleType === '2w') {
                            $payload['plan_type'] = 'Package (1 year OD + 1 year TP)';
                            $payload['pol_tenure'] = '1';
                        } else {
                            $payload['pol_plan_id'] = '02'; // Package Policy (1 Year OD + 1 Year TP)
                        }

                        // Return early to bypass further Standalone OD calculations
                        return;
                    }
                }

                if (empty($payload['ble_tp_start'])) {
                    $payload['ble_tp_start'] = $dorDate->format('Y-m-d');
                }

                if (empty($payload['ble_tp_end'])) {
                    $payload['ble_tp_end'] = $bundleTpEndDate->format('Y-m-d');
                }
            }
        }

        // 2. Resolve prev_pol_start_date and prev_pol_end_date if not present
        if (empty($payload['prev_pol_start_date'])) {
            $dates = $this->calculatePreviousPolicyDates($payload['pol_start_date']);
            $payload['prev_pol_start_date'] = $dates['prev_pol_start_date'];
            $payload['prev_pol_end_date']   = $dates['prev_pol_end_date'];
        }

        // 3. Set bundle OD start and end dates matching the previous policy period
        if (empty($payload['ble_od_start'])) {
            $payload['ble_od_start'] = $payload['prev_pol_start_date'];
        }
        if (empty($payload['ble_od_end'])) {
            $payload['ble_od_end'] = $payload['prev_pol_end_date'];
        }

        // 4. Default standard static fields
        $payload['ble_tp_tenure'] = $payload['ble_tp_tenure'] ?? (($vehicleType === '2w') ? '5' : '3');
        $payload['ble_tp_type']   = $payload['ble_tp_type'] ?? 'Liability';
        $payload['prev_pol_type'] = $payload['prev_pol_type'] ?? 'PackagePolicy';

        // 5. Resolve previous insurer name and policy number from history or use fallbacks
        $history = app(\App\Models\VehicleHistory::class)
            ->where('rc_number', strtolower(str_replace([' ', '-'], '', $payload['reg_no'] ?? '')))
            ->latest('_id')
            ->first();

        $historyInsCompany = null;
        $historyPolicyNo   = null;

        if ($history && !empty($history->response)) {
            $historyInsCompany = $history->response['insurance_company'] ?? null;
            $historyPolicyNo   = $history->response['insurance_policy_number'] ?? null;
        }

        $payload['ble_tp_name']      = $payload['ble_tp_name'] ?? $historyInsCompany ?? 'TATA AIG GENERAL INSURANCE';
        $payload['ble_tp_no']        = $payload['ble_tp_no'] ?? $historyPolicyNo ?? '3284920349';
        $payload['ble_saod_prev_no'] = $payload['ble_saod_prev_no'] ?? $historyPolicyNo ?? '1234567890';
    }
}
