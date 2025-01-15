<?php

namespace Mapbender\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

class ApiController extends AbstractController
{

    #[Route('/api/example', name: 'api_example', methods: ['GET'])]
    #[OA\Tag(name: 'test')]
    #[OA\Get(
        path: '/api/example',
        description: 'This endpoint just says hello',
        summary: 'A simple Hello World example',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success message with hello',
            )
        ]
    )]
    public function exampleAction()
    {
        return new JsonResponse(['message' => 'Hello, API!']);
    }

    #[Route('/api/test', name: 'api_test', methods: ['GET', 'POST'])]
    #[OA\Tag(name: 'test')]
    #[OA\Response(
        response: 200,
        description: 'Success message with request method',
    )]
    public function testAction(Request $request)
    {
        return new JsonResponse(['message' => $request->getMethod() . ' request successful.']);
    }
}
