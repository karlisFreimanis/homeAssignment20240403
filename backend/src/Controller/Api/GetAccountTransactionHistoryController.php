<?php

namespace App\Controller\Api;

use App\Dto\Api\GetUserTransactionHistoryDto;
use App\Service\Api\GetAccountTransactionHistoryService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetAccountTransactionHistoryController extends BaseApiController
{
    #[Route(
        path   : '/api/getAccountTransactionHistory',
        name   : 'get_user_transaction_history',
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/getAccountTransactionHistory',
        description: 'Retrieve account transaction history',
        summary: 'Get Account Transaction History',
        tags: ['Account Transactions'],
        parameters: [
            new OA\Parameter(
                name: 'accountId',
                description: 'ID of the account to retrieve transaction history for',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                          type: 'integer'
                      )
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Limit the number of transactions returned (default is 5)',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                          type: 'integer',
                          default: 5
                      )
            ),
            new OA\Parameter(
                name: 'offset',
                description: 'Offset for pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                          type: 'integer',
                          default: 0
                      )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\MediaType(
                              mediaType: 'application/json',
                              schema: new OA\Schema(
                                             type: 'object',
                                             properties: [
                                                       new OA\Property(
                                                           property: 'data',
                                                           type: 'array',
                                                           items: new OA\Items(
                                                                         properties: [
                                                                                         new OA\Property(
                                                                                             property: 'id',
                                                                                             type: 'integer'
                                                                                         ),
                                                                                         new OA\Property(
                                                                                             property: 'fromAccountId',
                                                                                             type: 'integer'
                                                                                         ),
                                                                                         new OA\Property(
                                                                                             property: 'toAccountId',
                                                                                             type: 'integer'
                                                                                         ),
                                                                                         new OA\Property(
                                                                                             property: 'status',
                                                                                             type: 'integer'
                                                                                         ),
                                                                                         new OA\Property(
                                                                                             property: 'fromAmount',
                                                                                             type: 'string'
                                                                                         ),
                                                                                         new OA\Property(
                                                                                             property: 'toAmount',
                                                                                             type: 'string'
                                                                                         ),
                                                                                         new OA\Property(
                                                                                             property: 'created',
                                                                                             type: 'string',
                                                                                             format: 'date-time'
                                                                                         ),
                                                                                         new OA\Property(
                                                                                             property: 'processed',
                                                                                             type: 'string',
                                                                                             format: 'date-time'
                                                                                         )
                                                                                     ]
                                                                     )
                                                       ),
                                                       new OA\Property(
                                                           property: 'total',
                                                           type: 'integer'
                                                       )
                                                   ]
                                         )
                          )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request',
                content: new OA\MediaType(
                              mediaType: 'application/json',
                              schema: new OA\Schema(
                                             type: 'object',
                                             properties: [
                                                       new OA\Property(
                                                           property: 'errorMessages',
                                                           type: 'array',
                                                           items: new OA\Items(
                                                                         type: 'string'
                                                                     )
                                                       )
                                                   ]
                                         )
                          )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\MediaType(
                              mediaType: 'application/json',
                              schema: new OA\Schema(
                                             type: 'object',
                                             properties: [
                                                       new OA\Property(
                                                           property: 'errorMessages',
                                                           type: 'array',
                                                           items: new OA\Items(
                                                                         type: 'string'
                                                                     )
                                                       )
                                                   ]
                                         )
                          )
            )
        ]
    )]
    public function getUserTransactionHistory(
        Request                             $request,
        GetAccountTransactionHistoryService $getUserTransactionHistoryService,
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

