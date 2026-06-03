<?php

namespace App\Http\Controllers\vehicleMapping;

use App\Http\Controllers\Controller;
use App\Services\VehicleMappingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
     * GET /api/insurance/tata-aig/makes
     */
    public function getMakes(): JsonResponse
    {
        $makes = $this->mappingService->getFormattedMakes();

        return response()->json([
            'success' => true,
            'data'    => $makes
        ]);
    }

    /**
     * GET /api/insurance/tata-aig/models?make_code=934&make_name=YEZDI
     */
    public function getModels(Request $request): JsonResponse
    {
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
    }

    /**
     * GET /api/insurance/tata-aig/trims?model_code=16310&model_name=ROADSTER
     */
    public function getTrims(Request $request): JsonResponse
    {
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
    }
}
