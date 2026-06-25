<?php

namespace App\Http\Controllers\vehicleMapping;

use App\Http\Controllers\Controller;
use App\Services\VehicleMappingService;
use App\Utils\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            return Response::success($this->mappingService->getFormattedMakes('2w'));
        } catch (Exception $e) {
            return Response::exeptionResponse($e);
        }
    }

    /**
     * GET /api/insurance/tata-aig/2w/makes
     */
    public function getMakes4w(): JsonResponse
    {
        try {
            return Response::success($this->mappingService->getFormattedMakes('4w'));
        } catch (Exception $e) {
            return Response::exeptionResponse($e);
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
                return Response::validationFailed('Make Code is Required');
            }

            return Response::success($this->mappingService->getFormattedModels($makeCode));
        } catch (Exception $e) {
            return Response::exeptionResponse($e);
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
                return Response::validationFailed('Model Code is Required');
            }

            return Response::success($this->mappingService->getFormattedTrims($modelCode));
        } catch (Exception $e) {
            return Response::exeptionResponse($e);
        }
    }
}
