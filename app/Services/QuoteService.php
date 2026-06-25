<?php

namespace App\Services;

use App\Services\Interfaces\QuoteServiceInterface;
use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Services\TataAigMasterService;
use App\Repositories\Interfaces\InsurerRepositoryInterface;
use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;

use Illuminate\Support\Facades\Log;
use Exception;
use Predis\Command\Argument\Search\SchemaFields\VectorField;

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

            // 2. Retrieve mapping details directly from payload
            $makeCode    = $payload['insurer_make_id'] ?? null;
            $modelCode   = $payload['insurer_model_id'] ?? null;
            $variantCode = $payload['insurer_trim_id'] ?? null;

            $makeName    = $payload['insurer_make_name'] ?? $payload['make_name'] ?? null;
            $modelName   = $payload['insurer_model_name'] ?? $payload['model_name'] ?? null;
            $variantName = $payload['insurer_trim_name'] ?? $payload['trim_name'] ?? null;

            // If the mapping IDs are missing or null, reject the request
            if (empty($makeCode) || empty($modelCode) || empty($variantCode)) {
                return [
                    'success' => false,
                    'message' => 'The selected vehicle details are not mapped for this insurer.'
                ];
            }

            // Inject carrier-specific nomenclature and codes into the payload
            $payload['vehicle_make']       = $makeName;
            $payload['vehicle_model']      = $modelName;
            $payload['vehicle_variant']    = $variantName;

            $productCode = $payload['product_code'] ?? '';
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


            // Default values  
            $payload['q_producer_code'] = $payload['q_producer_code'] ?? '4984727878';
            $payload['product_id'] = $payload['product_id'] ?? 'M300000000001';
            $payload['pol_start_date'] = $payload['pol_start_date'] ?? now()->format('Y-m-d');
            $payload['proposer_type'] = $payload['proposer_type'] ?? 'Individual';
            $payload['no_past_pol'] = $payload['no_past_pol'] ?? 'N';

            // Dynamically merge defaults when __finalize is '0' using null-coalescing
            if (!$isTwoWheeler && isset($payload['pol_plan_id'])) {
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
                    // Standalone TP: No Own Damage (OD) cover, no motor plan packages
                    $payload['pol_od_end_date'] = '';
                    $payload['motor_plan_opted_no'] = '';
                    $payload['dep_reimburse_claims'] = '';
                    $payload['engine_secure_options'] = '';
                    $payload['tyre_secure_options'] = '';
                } else if ($polPlanId === '04') {
                    // Package 1+3: New Business - Calculate 3-year TP dates
                    $payload['ble_tp_start'] = $payload['pol_start_date'];
                    $polStart = new \DateTime($payload['pol_start_date']);
                    $tpEnd = clone $polStart;
                    $tpEnd->modify('+3 years -1 day');
                    $payload['ble_tp_end'] = $tpEnd->format('Y-m-d');
                } else if ($polPlanId == '05') {
                    // Standalone OD: Active TP policy dates copy
                    $payload['ble_tp_start'] = $payload['prev_pol_start_date'] ?? '';
                    $payload['ble_tp_end'] = $payload['prev_pol_end_date'] ?? '';
                    // Auto-calculate expired OD policy dates to satisfy 90-day rule
                    $polStart = new \DateTime($payload['pol_start_date']);
                    $prevEnd = clone $polStart;
                    $prevEnd->modify('-1 day');
                    $prevStart = clone $prevEnd;
                    $prevStart->modify('-1 year +1 day');
                    $payload['prev_pol_start_date'] = $prevStart->format('Y-m-d');
                    $payload['prev_pol_end_date'] = $prevEnd->format('Y-m-d');
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
                    $motorPlan = $payload['motor_plan_opted_no'] ?? '';
                    if (in_array($motorPlan, ['P4', 'P6', 'P7', 'P11', 'P12'])) {
                        $payload['engine_secure_options'] = 'WITHOUT DEDUCTIBLE';
                    }
                    if (in_array($motorPlan, ['P5', 'P6', 'P7'])) {
                        $payload['tyre_secure_options'] = 'REPLACEMENT BASIS';
                    }
                    if (in_array($motorPlan, ['P3', 'P4', 'P5', 'P6', 'P7', 'P10', 'P11', 'P12'])) {
                        $payload['dep_reimburse_claims'] = '1';
                    }
                }
            }
            $payload['__finalize']                    = $payload['__finalize'] ?? '0';
            $payload['cng_lpg_cover']                 = $payload['cng_lpg_cover'] ?? 'false';
            $payload['automobile_association_cover']  = $payload['automobile_association_cover'] ?? 'false';
            $payload['antitheft_cover']               = $payload['antitheft_cover'] ?? 'false';
            $payload['tppd_discount']                 = $payload['tppd_discount'] ?? 'false';
            $payload['vehicle_blind']                 = $payload['vehicle_blind'] ?? 'false';
            $payload['vintage_car']                   = $payload['vintage_car'] ?? 'false';
            $payload['own_premises']                  = $payload['own_premises'] ?? 'false';
            $payload['ncb_protection']                = $payload['ncb_protection'] ?? 'false';
            $payload['add_towing']                    = $payload['add_towing'] ?? 'false';
            $payload['pa_owner_declaration']          = $payload['pa_owner_declaration'] ?? 'None';
            $payload['pa_unnamed']                    = $payload['pa_unnamed'] ?? 'false';
            $payload['pa_named']                      = $payload['pa_named'] ?? '';
            $payload['pa_paid']                       = $payload['pa_paid'] ?? 'false';
            $payload['load_fibre']                    = $payload['load_fibre'] ?? 'false';
            $payload['load_imported']                 = $payload['load_imported'] ?? 'false';
            $payload['load_tuition']                  = $payload['load_tuition'] ?? 'false';

            // Schema-required missing properties
            $payload['fleet_code']                 = $payload['fleet_code'] ?? '';
            $payload['fleet_name']                 = $payload['fleet_name'] ?? '';
            $payload['vehicle_idv']                = $payload['vehicle_idv'] ?? '';
            $payload['quote_id']                   = $payload['quote_id'] ?? '';
            $payload['side_car_idv']               = $payload['side_car_idv'] ?? '';
            $payload['non_electrical_si']          = $payload['non_electrical_si'] ?? '';
            $payload['electrical_si']              = $payload['electrical_si'] ?? '';
            $payload['voluntary_deductibles_amt']  = $payload['voluntary_deductibles_amt'] ?? '';
            $payload['voluntary_deductibles']      = $payload['voluntary_deductibles'] ?? 'false';
            // $payload['dep_reimburse_claims']       = $payload['dep_reimburse_claims'] ?? '';
            $payload['dep_reimburse_deductible']   = $payload['dep_reimburse_deductible'] ?? '';
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


            // Remove Droom-specific IDs before sending to Tata AIG
            unset($payload['make_id'], $payload['model_id'], $payload['trim_id']);
            // \Log::info($payload);
            // 4. Call Tata AIG Master Service directly
            $response = $this->tataAigService->fetchQuote($payload);
            $status = $response['success'] ? 'success' : 'failed';
            if ($status == 'failed') {
                return $response;
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
            // Log::error('Error fetching quote in QuoteService: ' . $e->getMessage(), [
            //     'payload' => $payload,
            //     'exception' => $e
            // ]);
            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }
    }

    public function updateQuotePaymentStatus(array $payload): array
    {
        try {
            if (empty($payload['quote_id']) && empty($payload['droom_payment_id']) && empty($payload['gateway_txn_id']) && empty($payload['payment_status']) && empty($payload['payment_id']) && empty($payload['selling_price'])) {
                return [
                    'success' => false,
                    'errors' => 'Something missing inyour payload.'
                ];
            }
            $getInsuranceObj = $this->quoteRepo->checkValidQuoteID($payload['quote_id']);
            if ($getInsuranceObj->status != 'quote_completed') {
                return [
                    'success' => false,
                    'errors' => 'Your quotation is not finalized.'
                ];
            }

            // Check Payable Amount
            if ($getInsuranceObj->net_premium != $payload['selling_price']) {
                return [
                    'success' => false,
                    'errors' => 'Payable amount mismatch.'
                ];
            }

            $updateData = $this->quoteRepo->updatePaymentStatus($payload);
            if ($updateData->status != "payment_success") {
                return [
                    'success' => false,
                    'errors' => 'Payment status not updated.please try again'
                ];
            }
            return [
                'success' => true,
                'data' => "Quote payment update sussceefully."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => 'An error occurred while fetching insurer quote: ' . $e->getMessage()
            ];
        }
    }
}
