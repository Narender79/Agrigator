<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ValidationExceptions;
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

            $insurer = $this->insurerService->createInsurer($validated);

            return Response::success($insurer, "Insurer created successfully", [], 201);
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }

    public function update(UpdateInsurerRequest $request, int $id)
    {
        try {
            $validated = $request->validated();

            $insurer = $this->insurerService->updateInsurer($validated, $id);

            return Response::success($insurer, "Insurer updated successfully", ['validated' => $request->all()], 201);
        } catch (ValidationExceptions $e) {

            return Response::validationFailed($e->getMessage());
        } catch (\Exception $e) {

            return Response::exeptionResponse($e);
        }
    }

    public function showByUserId(int $userId)
    {
        $insurer = $this->insurerService->getInsurerByUserId($userId);

        if (! $insurer) {
            return Response::failed('Insurer not found.', 404, ['message' => 'Insurer not found.'], 404);
        }

        return Response::success($insurer);
    }
}
