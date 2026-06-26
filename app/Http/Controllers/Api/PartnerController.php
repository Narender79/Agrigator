<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePostPartnerRequest;
use App\Http\Requests\Api\UpdatePostPartnerRequest;
use App\Services\Interfaces\PartnerServiceInterface;
use App\Utils\Response;

class PartnerController extends Controller
{
    public function __construct(private PartnerServiceInterface $partnerService) {}

    public function store(StorePostPartnerRequest $request)
    {
        try {
            $validated = $request->validated();

            return Response::success(
                $this->partnerService->createPartner($validated),
                ["message" => "Partner created successfully"],
                201
            );
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }

    public function update(UpdatePostPartnerRequest $request, int $id)
    {
        try {

            $validated = $request->validated();

            return Response::success(
                $this->partnerService->updatePartner($validated, $id),
                [
                    "message" => "Partner updated successfully",
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
            return Response::success($this->partnerService->showPartnerByUserId($userId));
        } catch (BusinessException $e) {

            return Response::failed($e->getMessage(), 404);
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }
}
