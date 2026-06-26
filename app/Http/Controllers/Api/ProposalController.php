<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePostProposalRequest;
use App\Services\Interfaces\ProposalServiceInterface;
use App\Utils\Response;

class ProposalController extends Controller
{
    public function __construct(private readonly ProposalServiceInterface $proposalService) {}

    public function store(StorePostProposalRequest $request)
    {
        try {
            // Check validation rule
            $request->validated();
            $result = $this->proposalService->createProposal($request->all());

            if (isset($result['success']) && $result['success'] === false) {
                return Response::failed(
                    data_get($result, 'errors'),
                    500,
                    ['data' => $result]
                );
            }
            return Response::success(data_get($result, 'data'), data_get($result, 'message'));
        } catch (\Exception $e) {
            return Response::exeptionResponse($e);
        }
    }
}
