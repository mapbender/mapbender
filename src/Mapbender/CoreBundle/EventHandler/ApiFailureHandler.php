<?php

namespace Mapbender\CoreBundle\EventHandler;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiFailureHandler extends AuthenticationFailureHandler
{

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof BadCredentialsException) {
            return new JsonResponse([
                "code" => Response::HTTP_UNAUTHORIZED,
                "message" => 'Invalid credentials.',
            ], Response::HTTP_UNAUTHORIZED);
        }
        return parent::onAuthenticationFailure($request, $exception);
    }

    /** @noinspection PhpUnused called by EventListener onLoginFailure (see services.xml) */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        if ($event->getFirewallName() === 'jwt_login') {
            $event->stopPropagation();
        }
    }

}
