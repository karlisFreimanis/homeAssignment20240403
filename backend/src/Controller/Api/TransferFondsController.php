<?php

namespace App\Controller\Api;

use App\Dto\Api\TransferFondsDto;
use App\Service\Api\TransferFondsApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TransferFondsController extends BaseApiController
{
    /**
     * @param Request                 $request
     * @param TransferFondsApiService $transferFondsApiService
     * @return JsonResponse
     */
    #[Route(
        path: '/api/transferFonds',
        name: 'api_transfer_fonds',
        methods: ['GET'],
    )]
    public function transferFonds(
        Request                 $request,
        TransferFondsApiService $transferFondsApiService,
    ): JsonResponse {
        $fromAccountId = $request->get('fromAccountId') ?? null;
        $toAccountId   = $request->get('toAccountId') ?? null;
        $amount        = $request->get('amount') ?? null;
        return $this->handleRequest(
            $this->validator->validate(new TransferFondsDto($request)),
            function () use ($fromAccountId, $toAccountId, $amount, $transferFondsApiService) {
                $result = $transferFondsApiService->fire($fromAccountId, $toAccountId, $amount);
                return new JsonResponse($result);
            },
        );
    }
}
