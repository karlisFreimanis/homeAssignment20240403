<?php

namespace App\Controller\Api;

use App\Dto\Api\GetUserTransactionHistoryDto;
use App\Service\Api\GetUserTransactionHistoryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetUserTransactionHistoryController extends BaseApiController
{
    #[Route(
        path   : '/api/getUserTransactionHistory',
        name   : 'get_user_transaction_history',
        methods: ['GET'],
    )]
    public function getUserTransactionHistory(
        Request $request,
        GetUserTransactionHistoryService $getUserTransactionHistoryService,
    ): JsonResponse {
        return $this->handleRequest(
            $this->validator->validate(new GetUserTransactionHistoryDto($request)),
            function () use ($getUserTransactionHistoryService, $request) {
                $result = $getUserTransactionHistoryService->handle($request);
                return new JsonResponse($result, Response::HTTP_OK);
            },
        );
    }

}

