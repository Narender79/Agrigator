<?php

namespace App\Repositories;

use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Models\Insurance\InsuranceQuoteMongo;
use App\Models\Insurance\InsurerQuoteResult;
use Illuminate\Support\Facades\Log;
use Exception;

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
            $quote = InsuranceQuoteMongo::create([
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
                'insurer_response_payload' => $data['insurer_response_payload'] ?? [],
            ]);

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
}
