<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\FrameworkBundle\Component\UserInfoProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserInfoController
{
    /** @var UserInfoProvider */
    protected $provider;

    public function __construct(UserInfoProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Provides current user information for client-side script evaluation.
     * Inherently not cachable, thus separate from config.
     *
     * @return JsonResponse
     * @since v3.2.2
     */
    #[Route(path: '/userinfo.json')]
    public function userinfoAction(): JsonResponse
    {
        return new JsonResponse($this->provider->getValues(), Response::HTTP_OK, array(
            'Vary' => 'Cookie',
        ));
    }
}
