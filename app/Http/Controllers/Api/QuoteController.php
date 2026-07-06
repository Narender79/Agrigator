<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SavePostQuotePaymentRequest;
use App\Http\Requests\Api\StorePostQuoteRequest;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Utils\Response;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteServiceInterface $quoteService) {}

    public function store(StorePostQuoteRequest $request)
    {
        try {
            // Check validation rule
            $request->validated();
            $result = $this->quoteService->getInsurerQuote($request->all());

            if (isset($result['success']) && $result['success'] === false) {
                $statusCode = data_get($result, 'status_code', 400);
                return Response::failed(
                    data_get($result, 'errors'),
                    $statusCode,
                    ['data' => $result],
                    $statusCode
                );
            }

            return Response::success($result);
        }catch (BusinessException $e) {
 
            return Response::failed($e->getMessage(), $e->getStatusCode(), [], $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }

    public function availableOptions(Request $request)
    {
        try {
            $vehicleType = $request->input('vehicle_type');

            $vehicleType = empty($vehicleType) ? '4w' : $vehicleType;

            $result['available_options'] = [
                'policy_plans' => config("insurance_masters.policy_plans.{$vehicleType}", []),
                'motor_plans' => config("insurance_masters.motor_plans.{$vehicleType}", []),
                'add_ons' => config("insurance_masters.add_ons.{$vehicleType}", []),
                'business_types' => config('insurance_masters.business_types'),
                'prev_policy_types' => config('insurance_masters.prev_policy_types'),
                'prev_policy_ncbs' => config('insurance_masters.prev_policy_ncbs'),
                'voluntary_amounts' => config('insurance_masters.voluntary_amounts'),
                'nominee_relationships' => config('insurance_masters.nominee_relationships'),
                'appointee_relationships' => config('insurance_masters.appointee_relationships'),
                'financier_types' => config('insurance_masters.financier_types'),
                'tyre_secure_options' => config('insurance_masters.tyre_secure_options'),
                'engine_secure_options' => config('insurance_masters.engine_secure_options'),
                'owner_driver_declarations' => config('insurance_masters.owner_driver_declarations'),
                'occupations' => config('insurance_masters.occupations'),
                'pre_insurer_options' => config('insurance_masters.pre_insurer_options', []),
            ];

            return Response::success($result);
        } catch (\Throwable $th) {
            return Response::exeptionResponse($th);
        }
    }

    public function saveQuotePayment(SavePostQuotePaymentRequest $request)
    {
        try {
            // Check validation rule
            $validatedData = $request->validated();

            $result = $this->quoteService->updateQuotePaymentStatus($validatedData);

            if (isset($result['success']) && $result['success'] === false) {
                $statusCode = data_get($result, 'status_code', 400);
                return Response::failed(
                    data_get($result, 'errors'),
                    $statusCode,
                    [],
                    $statusCode
                );
            }
            return Response::success(data_get($result, 'data'));
        } catch (ValidationExceptions $e) {
            \Log::info($e->getMessage());
            return Response::validationFailed($e->getMessage());
        } catch (BusinessException $e) {
            \Log::info($e->getMessage());
            return Response::failed($e->getMessage(), $e->getStatusCode(), [], $e->getStatusCode());
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            return Response::exeptionResponse($e);
        }
    }
}
