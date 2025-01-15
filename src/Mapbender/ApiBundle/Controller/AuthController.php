<?php

namespace Mapbender\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/*
 *  A fake controller for providing API documentation
 */

class AuthController extends AbstractController
{

    #[Route('/api/login_check', name: 'api_login_check_fake', methods: ['POST'])]
    #[OA\Tag(name: 'login')]
    #[OA\Post(
        description: 'This endpoint validates the provided credentials (username and password) and returns a token that can be used to authenticate other API requests',
        summary: 'Authenticates a user and returns a JWT',
        security: [], // We don't need a padlock icon here
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'username', description: 'The username of the user', type: 'string'),
                    new OA\Property(property: 'password', description: 'The password of the user', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful authentication',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', description: 'The JWT token', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function loginCheck(): void
    {
        // This endpoint is actually handled by the LexikJWTAuthenticationBundle.
        // This method exists only for API documentation purposes.
    }
}
