<?php

namespace App\Repositories;

use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Models\Insurance\QuoteReqRes;
use App\Models\Insurance\InsurerQuoteResult;
use \App\Models\Insurance\Quote;
use \App\Models\Insurance\UsersInsurance;
use \App\Models\Insurance\QuoteRequest;
use Exception;
use Override;

class QuoteRepository implements QuoteRepositoryInterface
{
    /**
     * Store a new quote request/response locally inside MongoDB.
     *
     * @param array $data
     * @return string The generated MongoDB ObjectId string
     */
    public function saveQuote(array $data): string
    {
        try {
            // \Log::info($data);
            // die();
            $quote = QuoteReqRes::create([
                'session_id'               => $data['session_id'] ?? null,
                'insurer_id'               => $data['insurer_id'] ?? null,
                'proposer_type'            => $data['proposer_type'] ?? null,
                'proposer_pincode'         => $data['proposer_pincode'] ?? null,
                'vehicle_make'             => $data['vehicle_make'] ?? null,
                'vehicle_model'            => $data['vehicle_model'] ?? null,
                'vehicle_variant'          => $data['vehicle_variant'] ?? null,
                'man_year'                 => $data['man_year'] ?? null,
                'price_offered'            => $data['price_offered'] ?? 0,
                'status'                   => $data['status'] ?? 'failed',
                'request_payload'          => $data['request_payload'] ?? [],
                'insurer_response_payload' => $data['insurer_response_payload']['data'] ?? [],
            ]);

            // 2. Save in SQL (if the insurer API returned a successful response)
            $response = $data['insurer_response_payload'] ?? null;
            if (!empty($response) && !empty($response['data'])) {
                $resData = $response['data'] ?? [];
                // Extract premium components
                $premiumBreakup = $resData['premium_break_up'] ?? [];
                $odPremium = $premiumBreakup['total_od_premium']['total_od'] ?? 0;
                $tpPremium = $premiumBreakup['total_tp_premium']['basic_tp_prem'] ?? 0;
                $addOnPremium = $premiumBreakup['total_addOns']['total_addon'] ?? 0;
                $paCoverPremium = $premiumBreakup['total_tp_premium']['pa_cover_prem'] ?? 0;
                $ncbDiscount = $premiumBreakup['total_addOns']['ncb_prem'] ?? 0;
                $cgst = $premiumBreakup['cgst_prem'] ?? 0;
                $sgst = $premiumBreakup['sgst_prem'] ?? 0;
                $igst = $premiumBreakup['igst_prem'] ?? 0;
                $gst  = $cgst + $sgst + $igst;
                $netPremium = $premiumBreakup['net_premium'] ?? 0;
                $totalPremium = $premiumBreakup['final_premium'] ?? 0;
                $vehicleIdv = $resData['pol_dlts']['vehicle_idv'] ?? 0;
                $userId = auth()->id() ?? request()->attributes->get('merchant_partner')?->user_id;
                Quote::create([
                    'user_id'                       => $userId,
                    'insurer_id'                    => $data['insurer_id'],
                    'quote_request_session_id'      => $data['session_id'] ?? null,
                    'vehicle_id'                    => $data['vehicle_id'] ?? null,
                    'product_id'                    => $data['request_payload']['product_id'] ?? null,
                    'coverage_type'                 => $data['request_payload']['prev_pol_type'] ?? 'Package',
                    'idv'                           => (float) $vehicleIdv,
                    'policy_term_months'            => isset($data['request_payload']['pol_tenure']) ? ((int) $data['request_payload']['pol_tenure'] * 12) : 12,
                    'selected_add_ons'              => $premiumBreakup['total_addOns'] ?? null,
                    'ncb_percentage'                => (int) ($data['request_payload']['pre_pol_ncb'] ?? 0),
                    'ncb_verified'                  => false,
                    'previous_policy_expiry'        => $data['request_payload']['prev_pol_end_date'] ?? null,
                    'is_new_vehicle'                => ($data['request_payload']['business_type_no'] ?? null) === '01',
                    'own_damage_premium'            => (float) $odPremium,
                    'third_party_premium'           => (float) $tpPremium,
                    'add_on_premium'                => (float) $addOnPremium,
                    'pa_cover_premium'              => (float) $paCoverPremium,
                    'ncb_discount'                  => (float) $ncbDiscount,
                    'voluntary_deductible'          => (float) ($data['request_payload']['voluntary_amount'] ?? 0),
                    'voluntary_deductible_discount' => 0.0,
                    'anti_theft_discount'           => 0.0,
                    'aai_discount'                  => 0.0,
                    'net_premium'                   => (float) $netPremium,
                    'gst'                           => (float) $gst,
                    'total_premium'                 => (float) $totalPremium,
                    'status'                        => 'active',
                    'valid_until'                   => now()->addDays(7),
                    'insurer_quote_ref'             => $resData['quote_id'] ?? null,
                    'source_channel'                => 'web',
                ]);
                UsersInsurance::updateOrCreate(
                    ['insurance_unique_id' => $data['session_id']],
                    [
                        'user_id'          => $userId,
                        'email'            => $data['request_payload']['email'] ?? null,
                        'name'             => $data['request_payload']['proposer_name'] ?? null,
                        'phone'            => $data['request_payload']['mobile_no'] ?? null,
                        'vehicleType'      => ($data['request_payload']['product_code'] ?? '') === '3187' ? '2w' : '4w',
                        'make'             => $data['vehicle_make'] ?? null,
                        'model'            => $data['vehicle_model'] ?? null,
                        'trim'             => $data['vehicle_variant'] ?? null,
                        'year'             => $data['man_year'] ?? null,
                        'quote_id'         => $resData['quote_id'] ?? null,
                        'quote_no'         => $resData['quote_no'] ?? null,
                        'proposal_id'      => $resData['proposal_id'] ?? null,
                        'product_id'       => $resData['product_id'] ?? null,
                        'product_group_id' => $resData['product_group_id'] ?? null,
                        'pol_plan_id'      => $resData['pol_plan_id'] ?? null,
                        'pol_plan_variant' => $resData['pol_plan_variant'] ?? null,
                        'policy_start_date' => $resData['pol_start_date'] ?? null,
                        'net_premium'      => $netPremium,
                        'status'           => "quote_" . $response['quote_stage'], // completed / inprogress
                        'platform'         => $data['request_payload']['platform'] ?? 'droom',
                        'source'           => $data['request_payload']['source'] ?? null,
                        'aggregator_name'  => 'TATA AIG',
                    ]
                );
            }

            return (string) $quote->_id;
        } catch (Exception $e) {
            // Log::error('Failed to save quote in MongoDB: ' . $e->getMessage(), [
            //     'data' => $data,
            //     'exception' => $e
            // ]);
            throw new \RuntimeException('Database logging error: ' . $e->getMessage());
        }
    }

    /**
     * Store the detailed insurer quote result inside MongoDB.
     */
    public function saveInsurerResult(array $data): string
    {
        try {
            $result = InsurerQuoteResult::create([
                'session_id'       => $data['session_id'],
                'insurer_slug'     => $data['insurer_slug'],
                'insurer_name'     => $data['insurer_name'] ?? null,
                'status'           => $data['status'],
                'premium_amount'   => $data['premium_amount'] ?? 0,
                'idv'              => $data['idv'] ?? 0,
                'coverage_details' => $data['coverage_details'] ?? [],
                'request_payload'  => $data['request_payload'] ?? [],
                'raw_response'     => $data['raw_response'] ?? [],
                'error_message'    => $data['error_message'] ?? null,
                'response_ms'      => $data['response_ms'] ?? 0,
            ]);

            return (string) $result->_id;
        } catch (Exception $e) {
            // Log::error('Failed to save insurer result in MongoDB: ' . $e->getMessage(), [
            //     'data' => $data,
            //     'exception' => $e
            // ]);
            throw new \RuntimeException('Database logging error: ' . $e->getMessage());
        }
    }

    public function firstOrCreateQuoteRequest(string $sessionId, array $data): QuoteRequest
    {
        return \App\Models\Insurance\QuoteRequest::firstOrCreate(
            ['session_id' => $sessionId],
            $data
        );
    }
}
