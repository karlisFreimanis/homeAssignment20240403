<?php

namespace App\Controller\Api;

use App\Dto\Api\GetUserAccountsDto;
use App\Service\Api\GetUserAccountsApiService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetUserAccountsController extends BaseApiController
{
    #[OA\Get(
        path: '/api/getUserAccounts',
        description: 'Retrieve user accounts',
        summary: 'Get User Accounts',
        tags: ['User Accounts'],
        parameters: [
            new OA\Parameter(
                name: 'userId',
                description: 'ID of the user whose accounts to retrieve',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                          type: 'integer'
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
                                             type: 'array',
                                             items: new OA\Items(
                                                       properties: [
                                                                       new OA\Property(
                                                                           property: 'userId',
                                                                           type: 'integer'
                                                                       ),
                                                                       new OA\Property(
                                                                           property: 'currencyId',
                                                                           type: 'integer'
                                                                       ),
                                                                       new OA\Property(
                                                                           property: 'balance',
                                                                           type: 'string'
                                                                       ),
                                                                       new OA\Property(
                                                                           property: 'name',
                                                                           type: 'string'
                                                                       )
                                                                   ]
                                                   )
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

    #[Route(
        path   : '/api/getUserAccounts',
        name   : 'api_get_user_accounts',
        methods: ['GET'],
    )]
    public function getUserAccounts(
        GetUserAccountsApiService $getAccountsApiService,
        Request                   $request,
    ): JsonResponse {
        return $this->handleRequest(
            $this->validator->validate(new GetUserAccountsDto($request)),
            function () use ($getAccountsApiService, $request) {
                $result = $getAccountsApiService->handle($request);
                return new JsonResponse($result, Response::HTTP_OK);
            },
        );
    }
}
