<?php

namespace App\Http\Controllers\TataAig;

use App\Http\Controllers\Controller;
use App\Services\TataAigMappingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TataAigMappingController extends Controller
{
    protected TataAigMappingService $mappingService;

    /**
     * Inject the Service Layer
     */
    public function __construct(TataAigMappingService $mappingService)
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
        $makeName = $request->query('make_name');

        if (!$makeCode && !$makeName) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide either make_code or make_name.'
            ], 400);
        }

        $models = $this->mappingService->getFormattedModels($makeCode, $makeName);

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
        $modelName = $request->query('model_name');

        if (!$modelCode && !$modelName) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide either model_code or model_name.'
            ], 400);
        }

        $trims = $this->mappingService->getFormattedTrims($modelCode, $modelName);

        return response()->json([
            'success' => true,
            'data'    => $trims
        ]);
    }
}
