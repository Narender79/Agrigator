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
                return [
                    'success' => false,
                    'message' => 'Insurer is not active or available.'
                ];
            }

            // finding the droom make_id from make name
            $makeObj = $this->vehicleMappingRepo->findMakeByName($payload['make_name'] ?? null);
            $modelObj = $this->vehicleMappingRepo->findModelByName($payload['model_name'] ?? null);
            $trimObj = $this->vehicleMappingRepo->findTrimByName($payload['trim_name'] ?? null);

            if (!$makeObj || !$modelObj || !$trimObj) {
                return [
                    'success' => false,
                    'message' => 'Unable to find Make name , Model name, Trim name.'
                ];
            }
            // \Log::info($payload);
            // 2. Fetch mapping details using Droom IDs
            $makeMapp = $this->vehicleMappingRepo->findMakeMapping($insurer->id, $makeObj->id ?? null);
            $modelMapp = $this->vehicleMappingRepo->findModelMapping($insurer->id, $modelObj->id ?? null);
            $trimMapp = $this->vehicleMappingRepo->findTrimMapping($insurer->id, $trimObj->id ?? null);

            // If no mapping is found for the selected vehicle, return a clear mapping error
            if (!$makeMapp || !$modelMapp || !$trimMapp) {
                return [
                    'success' => false,
                    'message' => 'The selected vehicle details are not mapped for this insurer.'
                ];
            }

            // Generate or capture quote request session ID
            $sessionId = $payload['session_id'] ?? (string) \Illuminate\Support\Str::uuid();
            $payload['session_id'] = $sessionId;
            // Store the quote request session in SQL
            $this->quoteRepo->firstOrCreateQuoteRequest($sessionId, [
                'vehicle_data' => [
                    'make_name'  => $payload['make_name'],
                    'model_name' => $payload['model_name'],
                    'trim_name'  => $payload['trim_name'],
                    'man_year' => $payload['man_year'] ?? null,
                ],
                'status' => 'complete',
                'insurer_count' => 1,
                'expires_at' => now()->addDays(7),
            ]);

            // 3. Inject carrier-specific nomenclature and codes into the payload
            $payload['vehicle_make']       = $makeMapp->insurer_make_name;
            $payload['vehicle_model']      = $modelMapp->insurer_model_name;
            $payload['vehicle_variant']    = $trimMapp->insurer_trim_name;

            $productCode = $payload['product_code'] ?? '';
            $isTwoWheeler = ($productCode === '3187');

            if ($isTwoWheeler) {
                $payload['make_code']    = (string) $makeMapp->insurer_make_id;
                $payload['model_code']   = (string) $modelMapp->insurer_model_id;
                $payload['variant_code'] = (string) $trimMapp->insurer_trim_id;
                if (isset($payload['man_year'])) {
                    $payload['man_year'] = (string) $payload['man_year'];
                }
            } else {
                $payload['vehicle_make_no']    = (int) $makeMapp->insurer_make_id;
                $payload['vehicle_model_no']   = (int) $modelMapp->insurer_model_id;
                $payload['vehicle_variant_no'] = (string) $trimMapp->insurer_trim_id;
                if (isset($payload['man_year'])) {
                    $payload['man_year'] = (int) $payload['man_year'];
                }
            }

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
            $payload['prev_pol_type'] = $payload['prev_pol_type'] ?? 'Package';

            // Dynamically merge defaults when __finalize is '0' using null-coalescing
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
            $payload['pa_owner']                   = $payload['pa_owner'] ?? 'false';
            $payload['vehicle_idv']                = $payload['vehicle_idv'] ?? '';
            $payload['quote_id']                   = $payload['quote_id'] ?? '';
            $payload['side_car_idv']               = $payload['side_car_idv'] ?? '';
            $payload['non_electrical_si']          = $payload['non_electrical_si'] ?? '';
            $payload['electrical_si']              = $payload['electrical_si'] ?? '';
            $payload['voluntary_deductibles_amt']  = $payload['voluntary_deductibles_amt'] ?? '';
            $payload['voluntary_deductibles']      = $payload['voluntary_deductibles'] ?? 'false';
            $payload['dep_reimburse_claims']       = $payload['dep_reimburse_claims'] ?? '';
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
            $payload['place_reg_no']               = $payload['place_reg_no'] ?? '101';
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
            $vehicleType = ($modelMapp && $modelMapp->category_id == 81) ? '4w' : '2w';
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
                'tyre_secure_options'       => config('insurance_masters.tyre_secure_options'),
                'engine_secure_options'     => config('insurance_masters.engine_secure_options'),
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
                'message' => 'An error occurred while fetching insurer quote: ' . $e->getMessage()
            ];
        }
    }
}
