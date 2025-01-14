<?php

namespace Mapbender\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{

    #[Route('/api/example', name: 'api_example', methods: ['GET'])]
    public function example()
    {
        return new JsonResponse(['message' => 'Hello, API!']);
    }

}
