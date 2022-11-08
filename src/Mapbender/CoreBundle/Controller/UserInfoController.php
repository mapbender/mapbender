<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\FrameworkBundle\Component\UserInfoProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/userinfo.json")
     * @return JsonResponse
     * @since v3.2.2
     */
    public function userinfoAction()
    {
        return new JsonResponse($this->provider->getValues(), 200, array(
            'Vary' => 'Cookie',
        ));
    }
}
