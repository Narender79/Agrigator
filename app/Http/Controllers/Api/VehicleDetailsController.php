<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Utils\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\DroomAuthService;

class VehicleDetailsController extends Controller
{
    protected DroomAuthService $authService;

    public function __construct(DroomAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Retrieve vehicle details by RC number and match MMYT.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetails(Request $request)
    {
        try {
            // Validate the query input
            $validator = Validator::make($request->all(), [
                'rc_number' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return Response::validationFailed($validator->errors());
            }

            $rcNumber = $request->query('rc_number');

            $gatewayUrl = config('services.droom.gateway_url');
            
            // Get OAuth token dynamically
            $tokenRes = $this->authService->getOAuthToken();
            if (($tokenRes['code'] ?? '') !== 'success') {
                $errorMsg = !empty($tokenRes['errors']) ? implode(', ', $tokenRes['errors']) : 'Failed to retrieve OAuth token.';
                return Response::failed($errorMsg, 500, [], 500);
            }
            $dTkn = $tokenRes['data']['token'] ?? null;

            if (empty($dTkn)) {
                return Response::failed('DSS API Authorization Token is not configured.', 500, [], 500);
            }

            Log::info('Fetching vehicle RC details', ['rc_number' => $rcNumber]);

            // Call DSS Vehicle RC Details API (Curl 1)
            $response1 = Http::withHeaders([
                'Authorization' => 'Bearer ' . $dTkn,
            ])->get($gatewayUrl . '/dss/v1/vehicle-rc-details', [
                'rc_number' => $rcNumber,
            ]);

            if (!$response1->successful()) {
                Log::error('Failed to fetch RC details', [
                    'rc_number' => $rcNumber,
                    'status' => $response1->status(),
                    'body' => $response1->body(),
                ]);
                return Response::failed('Failed to retrieve vehicle details from DSS API.', $response1->status(), [
                    'message' => 'DSS API returned a non-success status.'
                ], $response1->status());
            }

            $data1 = $response1->json();

            if (($data1['code'] ?? '') !== 'success' || empty($data1['data'])) {
                Log::warning('DSS API returned unsuccessful code or empty data', [
                    'rc_number' => $rcNumber,
                    'response' => $data1,
                ]);
                return Response::failed($data1['message'] ?? 'DSS API returned unsuccessful status.', 400, [], 400);
            }

            // Extract the required fields for MMYT Matching
            $vehicleData = $data1['data'];
            $vehicleCompany = $vehicleData['vehicle_company'] ?? null;
            $vehicleModel = $vehicleData['vehicle_model'] ?? null;
            $registrationDate = $vehicleData['registration_date'] ?? null;
            $vehicleCategory = $vehicleData['vehicle_category'] ?? null;

            Log::info('Retrieved RC details successfully, mapping MMYT...', [
                'vehicle_company' => $vehicleCompany,
                'vehicle_model' => $vehicleModel,
                'registration_date' => $registrationDate,
                'vehicle_category' => $vehicleCategory,
            ]);

            // Prepare POST body for MMYT Matching API (Curl 2)
            $postBody = [
                'vehicle_company' => $vehicleCompany,
                'vehicle_model' => $vehicleModel,
                'registration_date' => $registrationDate,
                'vehicle_category' => $vehicleCategory,
            ];

            $genaiUrl = config('services.droom.genai_base_url');
            $genaiToken = config('services.droom.genai_auth_token');

            if (empty($genaiToken)) {
                return Response::failed('GenAI API Authorization Token is not configured.', 500, [], 500);
            }

            // Call GenAI MMYT Matching API (Curl 2)
            $response2 = Http::withHeaders([
                'Authorization' => $genaiToken,
                'Cookie' => 'locale=ZW4tSU4=',
            ])->post($genaiUrl . '/get-mmyt', $postBody);

            if (!$response2->successful()) {
                Log::error('Failed to get MMYT matching', [
                    'body_sent' => $postBody,
                    'status' => $response2->status(),
                    'body' => $response2->body(),
                ]);
                return Response::failed('Failed to match MMYT details from GenAI API.', $response2->status(), [
                    'message' => 'GenAI API returned a non-success status.'
                ], $response2->status());
            }

            $data2 = $response2->json();

            if (($data2['code'] ?? '') !== 'success') {
                Log::warning('GenAI API returned unsuccessful matching status', [
                    'body_sent' => $postBody,
                    'response' => $data2,
                ]);
                return Response::failed($data2['message'] ?? 'MMYT matching failed.', 400, [], 400);
            }

            // Return the successful response back to the user
            return Response::success(
                $data2['data'] ?? [],
                [
                    'message' => $data2['message'] ?? 'MMYT matched successfully!'
                ]
            );

        } catch (\Throwable $th) {
            Log::error('Exception in VehicleDetailsController', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return Response::failed(
                'Internal server error.',
                500,
                ['message' => $th->getMessage()],
                500
            );
        }
    }
}
