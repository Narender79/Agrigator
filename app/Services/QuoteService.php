<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationExceptions;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Services\TataAigMasterService;
use App\Repositories\Interfaces\InsurerRepositoryInterface;
use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;
use \App\Utils\Logger;
use Exception;

class QuoteService implements QuoteServiceInterface
{
    protected InsurerRepositoryInterface $insurerRepo;
    protected VehicleMappingRepositoryInterface $vehicleMappingRepo;
    public function __construct(
        private readonly QuoteRepositoryInterface $quoteRepo,
        private readonly TataAigMasterService $tataAigService,
        InsurerRepositoryInterface $insurerRepo,
        VehicleMappingRepositoryInterface $vehicleMappingRepo
    ) {
        $this->insurerRepo = $insurerRepo;
        $this->vehicleMappingRepo = $vehicleMappingRepo;
    }

    public function getInsurerQuote(array $payload): array
    {
        try {
            // 1. Resolve Insurer 
            $insurer = $this->insurerRepo->getActiveByName('TATA AIG GENERAL INSURANCE');

            if (!$insurer || $insurer->status !== 'active') {
                throw new Exception("Insurer is not active or available.");
            }

            $makeCode    = $payload['insurer_make_id'] ?? null;
            $modelCode   = $payload['insurer_model_id'] ?? null;
            $variantCode = $payload['insurer_trim_id'] ?? null;

            $makeName    = $payload['insurer_make_name'] ?? $payload['make_name'] ?? null;
            $modelName   = $payload['insurer_model_name'] ?? $payload['model_name'] ?? null;
            $variantName = $payload['insurer_trim_name'] ?? $payload['trim_name'] ?? null;

            // If the mapping IDs are missing or null, reject the request
            if (empty($makeCode) || empty($modelCode) || empty($variantCode)) {
                $msg = 'The selected vehicle details are not mapped for this insurer.';
                Logger::logException($msg, 400, ['payload' => $payload]);
                return [
                    'success' => false,
                    'errors' => $msg,
                    'status_code' => 400
                ];
            }

            // Inject carrier-specific nomenclature and codes into the payload
            $payload['vehicle_make']       = $makeName;
            $payload['vehicle_model']      = $modelName;
            $payload['vehicle_variant']    = $variantName;

            $vehicleType = $payload['vehicle_type'] ?? '4w';
            if ($vehicleType === '2w') {
                $payload['product_code'] = '3187';
            } else {
                $payload['product_code'] = '3184';
            }
            $productCode = $payload['product_code'];
            $isTwoWheeler = ($productCode === '3187');

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
            $regNo = strtoupper(str_replace([' ', '-'], '', $payload['reg_no'] ?? ''));
            $regno_1 = '';
            $regno_2 = '';
            $regno_3 = '';
            $regno_4 = '';

            // Example: HR98W9712 -> regno_1: HR, regno_2: 98, regno_3: W, regno_4: 9712
            if (preg_match('/^([A-Z]{2})([0-9]{1,2})([A-Z]{0,2})([0-9]{1,4})$/i', $regNo, $matches)) {
                $regno_1 = $matches[1];
                $regno_2 = str_pad($matches[2], 2, '0', STR_PAD_LEFT); // Ensures 2 digits (e.g. 4 -> 04)
                $regno_3 = $matches[3];
                $regno_4 = $matches[4];
            }

            // Inject the parts back into the payload for Tata AIG and saving in DB
            $payload['regno_1'] = $regno_1;
            $payload['regno_2'] = $regno_2;
            $payload['regno_3'] = $regno_3;
            $payload['regno_4'] = $regno_4;

            // Resolve place_reg_no (RTO code) dynamically based on registration prefix
            if (empty($payload['place_reg_no']) && !empty($regno_1) && !empty($regno_2)) {
                $rtoResponse = $this->tataAigService->fetchRto();
                if (isset($rtoResponse['status']) && $rtoResponse['status'] == 200 && !empty($rtoResponse['data'])) {
                    foreach ($rtoResponse['data'] as $item) {
                        if (strcasecmp($item['code1'], $regno_1) === 0 && strcasecmp($item['code2'], $regno_2) === 0) {
                            $payload['place_reg_no'] = (string) $item['code'];
                            break;
                        }
                    }
                }
            }

            // Default values  
            $payload['q_producer_code'] = $payload['q_producer_code'] ?? '4984727878';
            $payload['product_id'] = $payload['product_id'] ?? 'M300000000001';
            $payload['pol_start_date'] = $payload['pol_start_date'] ?? now()->format('Y-m-d');
            $payload['proposer_type'] = $payload['proposer_type'] ?? 'Individual';
            $payload['no_past_pol'] = $payload['no_past_pol'] ?? 'N';

            if (!$isTwoWheeler) {
                // --- FOUR WHEELER (4W) POLICY LOGIC & DEFAULTS ---
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
                            $payload['ble_tp_start'] = $payload['prev_pol_start_date'] ?? '';
                            $payload['ble_tp_end'] = $payload['prev_pol_end_date'] ?? '';
                            $prevEnd = clone $polStart;
                            $prevEnd->modify('-1 day');
                            $prevStart = clone $prevEnd;
                            $prevStart->modify('-1 year +1 day');
                            $payload['prev_pol_start_date'] = $prevStart->format('Y-m-d');
                            $payload['prev_pol_end_date'] = $prevEnd->format('Y-m-d');
                        }
                    }

                    // 3. Handle PA Owner Cover
                    if ($polPlanId === '05') {
                        $payload['pa_owner'] = 'No';
                        $payload['pa_owner_tenure'] = '';
                    } else {
                        $paOwner = strtolower($payload['pa_owner'] ?? '');
                        if ($paOwner === 'true' || $paOwner === 'yes' || $paOwner === '1') {
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
                    'rtn_invoice',
                    'cng_lpg',
                    'dep_reimburse',
                    'tyre_secure',
                    'engine_secure',
                    'key_replace',
                    'repair_glass',
                    'return_invoice',
                    'personal_loss',
                    'emergency_expense',
                    'consumbale_expense',
                    'rsa',
                    'add_towing',
                    'own_premises',
                    'automobile_association_cover',
                    'prev_tyre',
                    'prev_engine',
                    'prev_dep',
                    'prev_consumable',
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
                foreach ($booleanNoKeys as $key) {
                    $payload[$key] = $payload[$key] ?? 'No';
                }

                $payload['special_regno'] = $payload['special_regno'] ?? 'false';
                $payload['no_past_pol'] = $payload['no_past_pol'] ?? 'N';

                $payload['cng_lpg_cover']                 = $payload['cng_lpg_cover'] ?? 'NO';
                $payload['vintage_car']                   = $payload['vintage_car'] ?? 'No';
                $payload['ncb_protection']                = $payload['ncb_protection'] ?? 'No';
                $payload['load_fibre']                    = $payload['load_fibre'] ?? 'No';
                $payload['load_imported']                 = $payload['load_imported'] ?? 'No';
                $payload['load_tuition']                  = $payload['load_tuition'] ?? 'No';
            } else {
                // --- TWO WHEELER (2W) POLICY LOGIC & DEFAULTS ---
                // 2. Map and format PA Owner Cover
                $paOwner = strtolower($payload['pa_owner'] ?? '');
                if ($paOwner === 'true' || $paOwner === 'yes' || $paOwner === '1') {
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

                // 3. Previous Policy properties mapping
                $payload['prev_pol_type'] = $payload['prev_pol_type'] ?? 'PackagePolicy';
                if (empty($payload['prev_pol_start_date'])) {
                    $polStart = new \DateTime($payload['pol_start_date']);
                    $prevEnd = clone $polStart;
                    $prevEnd->modify('-1 day');
                    $prevStart = clone $prevEnd;
                    $prevStart->modify('-1 year +1 day');
                    $payload['prev_pol_start_date'] = $prevStart->format('Y-m-d');
                    $payload['prev_pol_end_date'] = $prevEnd->format('Y-m-d');
                }

                // 4. Default other covers required by the 2W API schema to 'false'
                $booleanKeys = [
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
                    'special_regno',
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
                foreach ($booleanKeys as $key) {
                    $payload[$key] = $payload[$key] ?? 'false';
                }

                $payload['dep_reimb'] = $payload['dep_reimb'] ?? 'true';
                $payload['no_past_pol'] = $payload['no_past_pol'] ?? 'false';

                $payload['cng_lpg_cover']                 = $payload['cng_lpg_cover'] ?? 'false';
                $payload['vintage_car']                   = $payload['vintage_car'] ?? 'false';
                $payload['ncb_protection']                = $payload['ncb_protection'] ?? 'false';
                $payload['load_fibre']                    = $payload['load_fibre'] ?? 'false';
                $payload['load_imported']                 = $payload['load_imported'] ?? 'false';
                $payload['load_tuition']                  = $payload['load_tuition'] ?? 'false';
            }

            // --- SHARED DEFAULTS FOR BOTH 2W AND 4W ---
            $payload['__finalize']                    = $payload['__finalize'] ?? '0';
            $payload['pa_owner_declaration']          = $payload['pa_owner_declaration'] ?? 'None';
            $payload['pa_named']                      = $payload['pa_named'] ?? '';
            $payload['prev_cnglpg']                   = $payload['prev_cnglpg'] ?? 'false';
            $payload['pa_paid_no']                    = $payload['pa_paid_no']        ?? '';
            $payload['pa_paid_si']                    = $payload['pa_paid_si']        ?? '';
            $payload['pa_unnamed_no']                 = $payload['pa_unnamed_no']     ?? '';
            $payload['pa_unnamed_si']                 = $payload['pa_unnamed_si']     ?? '';
            $payload['add_towing_amount']             = $payload['add_towing_amount'] ?? '';
            $payload['ll_paid_no']                    = $payload['ll_paid_no']        ?? '';
            $payload['ll_emp_no']                     = $payload['ll_emp_no']         ?? '';
            $payload['fleet_policy']                  = $payload['fleet_policy'] ?? 'false';
            $payload['fleet_code']                    = $payload['fleet_code'] ?? '';
            $payload['fleet_name']                    = $payload['fleet_name'] ?? '';



            // Schema-required missing properties
            $payload['vehicle_idv']                = $payload['vehicle_idv'] ?? '';
            $payload['quote_id']                   = $payload['quote_id'] ?? '';
            $payload['side_car_idv']               = $payload['side_car_idv'] ?? '';
            $payload['non_electrical_si']          = $payload['non_electrical_si'] ?? '';
            $payload['electrical_si']              = $payload['electrical_si'] ?? '';
            $payload['voluntary_deductibles_amt']  = $payload['voluntary_deductibles_amt'] ?? '';
            $payload['dep_reimburse_claims']       = $payload['dep_reimburse_claims'] ?? '';
            $payload['dep_reimburse_deductible']   = $payload['dep_reimburse_deductible'] ?? '';
            $payload['driver_age']                 = $payload['driver_age'] ?? '';
            $payload['driver_gender']              = $payload['driver_gender'] ?? '';
            $payload['driver_occupation']          = $payload['driver_occupation'] ?? '';
            $payload['claim_last_count']           = $payload['claim_last_count'] ?? '';
            $payload['claim_last_amount']          = $payload['claim_last_amount'] ?? '';
            $payload['veh_plying_city']            = $payload['veh_plying_city'] ?? '';
            $payload['non_electrical_des']         = $payload['non_electrical_des'] ?? '';
            $payload['electrical_des']             = $payload['electrical_des'] ?? '';
            $payload['emg_med_exp_si']             = $payload['emg_med_exp_si'] ?? '';
            $payload['add_tppd_si']                = $payload['add_tppd_si'] ?? '';
            $payload['add_pa_owner_si']            = $payload['add_pa_owner_si'] ?? '';
            $payload['add_pa_unnamed_si']          = $payload['add_pa_unnamed_si'] ?? '';
            $payload['event_name']                 = $payload['event_name'] ?? '';
            $payload['promoter_name']              = $payload['promoter_name'] ?? '';
            $payload['event_from_date']            = $payload['event_from_date'] ?? '';
            $payload['event_to_date']              = $payload['event_to_date'] ?? '';
            $payload['loss_accessories_idv']       = $payload['loss_accessories_idv'] ?? '';
            // $payload['place_reg_no']               = $payload['place_reg_no'];
            $payload['q_producer_email']           = $payload['q_producer_email'] ?? 'pi_uat@tataaig.com';
            $payload['uw_loading']                 = $payload['uw_loading'] ?? '';
            $payload['uw_discount']                = $payload['uw_discount'] ?? '';
            $payload['voluntary_amount']           = $payload['voluntary_amount'] ?? '';
            $payload['cng_lpg_si']                 = $payload['cng_lpg_si'] ?? '';
            $payload['automobile_association_mem_exp_date'] = $payload['automobile_association_mem_exp_date'] ?? '';
            $payload['automobile_association_mem_no']       = $payload['automobile_association_mem_no']       ?? '';


            // Remove Droom-specific IDs before sending to Tata AIG
            unset($payload['make_id'], $payload['model_id'], $payload['trim_id']);
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
                return [
                    'success' => false,
                    'errors' => $response['errors'] ?? $errorMessage,
                    'status_code' => 400
                ];
            }
            // die();
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
            // die();
            // Check the category_id from the model mapping to determine 4w vs 2w dynamically
            $vehicleType = $payload['vehicle_type'] ?? '4w';
            $result['available_options'] = [
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

            $result['session_id'] = $sessionId;
            return $result;
        } catch (Exception $e) {
            Logger::logException($e);
            return [
                'success' => false,
                'errors' => $e->getMessage(),
                'status_code' => 500
            ];
        }
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
            'data' => "Quote payment update sussceefully."
        ];
    }
}
