<?php

namespace App\Http\Controllers\vehicleMapping;

use App\Http\Controllers\Controller;
use App\Services\VehicleMappingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class VehicleMappingController extends Controller
{
    protected VehicleMappingService $mappingService;

    /**
     * Inject the Service Layer
     */
    public function __construct(VehicleMappingService $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    /**
     * GET /api/insurance/tata-aig/2w/makes
     */
    public function getMakes2w(): JsonResponse
    {
        try {
            $makes = $this->mappingService->getFormattedMakes('2w');

            return response()->json([
                'success' => true,
                'data'    => $makes
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch 2w makes: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vehicle makes.'
            ], 500);
        }
    }

    /**
     * GET /api/insurance/tata-aig/2w/makes
     */
    public function getMakes4w(): JsonResponse
    {
        try {
            $makes = $this->mappingService->getFormattedMakes('4w');

            return response()->json([
                'success' => true,
                'data'    => $makes
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch 4w makes: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vehicle makes.'
            ], 500);
        }
    }
    /**
     * GET /api/insurance/tata-aig/models?make_code=xxx
     */
    public function getModels(Request $request): JsonResponse
    {
        try {
            $makeCode = $request->query('make_code');

            if (!$makeCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide either make_code or make_name.'
                ], 400);
            }

            $models = $this->mappingService->getFormattedModels($makeCode);

            return response()->json([
                'success' => true,
                'data'    => $models
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch models: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vehicle models.'
            ], 500);
        }
    }

    /**
     * GET /api/insurance/tata-aig/trims?model_code=xxxxxx&model_name=xxxx
     */
    public function getTrims(Request $request): JsonResponse
    {
        try {
            $modelCode = $request->query('model_code');

            if (!$modelCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide either model_code or model_name.'
                ], 400);
            }

            $trims = $this->mappingService->getFormattedTrims($modelCode);

            return response()->json([
                'success' => true,
                'data'    => $trims
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch trims: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vehicle trims.'
            ], 500);
        }
    }
}
