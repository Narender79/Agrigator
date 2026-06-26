<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInsurerRequest;
use App\Http\Requests\Api\UpdateInsurerRequest;
use App\Services\Interfaces\InsurerServiceInterface;
use App\Utils\Response;

class InsurerController extends Controller
{
    public function __construct(private readonly InsurerServiceInterface $insurerService) {}

    public function store(StoreInsurerRequest $request)
    {
        try {
            $validated = $request->validated();

            return Response::success(
                $this->insurerService->createInsurer($validated),
                ["message" => "Insurer created successfully"],
                201
            );
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }

    public function update(UpdateInsurerRequest $request, int $id)
    {
        try {
            $validated = $request->validated();

            return Response::success(
                $this->insurerService->updateInsurer($validated, $id),
                [
                    "message" => "Insurer updated successfully",
                    'validated' => $request->all()
                ],
                201
            );
        } catch (BusinessException $e) {

            return Response::failed($e->getMessage(), 404);
        } catch (\Exception $e) {

            return Response::exeptionResponse($e);
        }
    }

    public function showByUserId(int $userId)
    {
        try {
            return Response::success($this->insurerService->getInsurerByUserId($userId));
        } catch (BusinessException $e) {

            return Response::failed($e->getMessage(), 404);
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }
}
