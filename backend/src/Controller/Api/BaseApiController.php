<?php

namespace App\Controller\Api;

use App\Exception\ApiValidationException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class BaseApiController extends AbstractController
{
    public function __construct(
        protected readonly ValidatorInterface $validator,
    )
    {

    }

    /**
     * @OA\Info(title="My First API", version="0.1")
     */

    /**
     * @OA\Get(
     *     path="/api/resource.json",
     *     @OA\Response(response="200", description="An example resource")
     * )
     */

    #[OA\Get(
        path: '/api/ping',
        operationId: 'ping',
        description: 'Pings the service',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Return ping',
                content: new OA\JsonContent(
                              type: 'string',
                              example: 'ping',
                          ),
            ),
        ]
    )]
    #[Route(
        path: '/api/ping',
        name: 'api_ping',
        methods: ['GET'],
    )]
    /**
     * @return JsonResponse
     */
    final public function ping(): JsonResponse
    {
        return new JsonResponse(['ping' => true]);
    }

    /**
     * @param ConstraintViolationListInterface $validationErrors
     * @param callable                         $callback
     * @return JsonResponse
     */
    protected function handleRequest(
        ConstraintViolationListInterface $validationErrors,
        callable                         $callback,
    ): JsonResponse {
        if ($validationErrors->count() > 0) {
            $errorMessages = [];
            foreach (range(1, $validationErrors->count()) as $counter) {
                $validationError = $validationErrors->get($counter - 1);
                $errorMessages[] = $validationError->getPropertyPath() . ': ' . $validationError->getMessage();
            }
            return $this->handleError($errorMessages, Response::HTTP_BAD_REQUEST);
        }

        try {
            return $callback();
        } catch (ApiValidationException $apiValidationException) {
            return $this->handleError([$apiValidationException->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $throwable) {
            return $this->handleError([$throwable->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array $errorMessages
     * @param int   $response
     * @return JsonResponse
     */
    protected function handleError(array $errorMessages, int $response): JsonResponse
    {
        return new JsonResponse(
            [
                'errorMessages' => $errorMessages,
            ],
            $response,
        );
    }
}
