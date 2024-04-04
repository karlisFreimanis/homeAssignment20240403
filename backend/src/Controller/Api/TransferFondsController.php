<?php

namespace App\Controller\Api;

use App\Dto\Api\TransferFondsDto;
use App\Service\Api\TransferFondsApiService;
use OpenApi\Attributes as OA;
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
    #[OA\Get(
        path: '/api/transferFonds',
        description: 'Transfer funds from one account to another',
        summary: 'Transfer Funds',
        tags: ['Transfer Funds'],
        parameters: [
            new OA\Parameter(
                name: 'fromAccountId',
                description: 'ID of the account to transfer funds from',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                          type: 'integer'
                      )
            ),
            new OA\Parameter(
                name: 'toAccountId',
                description: 'ID of the account to transfer funds to',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                          type: 'integer'
                      )
            ),
            new OA\Parameter(
                name: 'amount',
                description: 'Amount of funds to transfer',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                          type: 'number'
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
                                             properties: [
                                                       new OA\Property(
                                                           property: 'id',
                                                           type: 'integer'
                                                       )
                                                   ],
                                             type      : 'object'
                                         )
                          )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request',
                content: new OA\MediaType(
                              mediaType: 'application/json',
                              schema: new OA\Schema(
                                             properties: [
                                                       new OA\Property(
                                                           property: 'errorMessages',
                                                           type: 'array',
                                                           items: new OA\Items(
                                                                         type: 'string'
                                                                     )
                                                       )
                                                   ],
                                             type      : 'object'
                                         )
                          )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\MediaType(
                              mediaType: 'application/json',
                              schema: new OA\Schema(
                                             properties: [
                                                       new OA\Property(
                                                           property: 'errorMessages',
                                                           type: 'array',
                                                           items: new OA\Items(
                                                                         type: 'string'
                                                                     )
                                                       )
                                                   ],
                                             type      : 'object'
                                         )
                          )
            )
        ]
    )]
    public function transferFonds(
        Request                 $request,
        TransferFondsApiService $transferFondsApiService,
    ): JsonResponse {
        return $this->handleRequest(
            $this->validator->validate(new TransferFondsDto($request)),
            function () use ($request, $transferFondsApiService) {
                $result = $transferFondsApiService->handle($request);
                return new JsonResponse($result);
            },
        );
    }
}
