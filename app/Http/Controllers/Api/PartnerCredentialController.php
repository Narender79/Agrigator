<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePostPartnerCredentialRequest;
use App\Services\Interfaces\PartnerServiceInterface;
use App\Utils\Response;

class PartnerCredentialController extends Controller
{
    public function __construct(private PartnerServiceInterface $partnerService) {}

    public function store(StorePostPartnerCredentialRequest $request)
    {
        try {
            $validated = $request->validated();

            return Response::success(
                $this->partnerService->createPartnerCredential($validated),
                ["message" => "Partner credential created successfully"],
                201
            );
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }

    public function showByPartnerId(int $partnerId)
    {
        try {
            return Response::success($this->partnerService->getPartnerCredentialByPartnerId($partnerId));
        } catch (BusinessException $e) {

            return Response::failed($e->getMessage(), 404);
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }
}
