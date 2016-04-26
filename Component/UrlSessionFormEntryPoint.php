<?php

namespace Mapbender\Component;
namespace Mapbender\Component;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class UrlSessionFormEntryPoint extends FormAuthenticationEntryPoint {
    private $loginPath;
    private $useForward;
    private $httpKernel;
    private $httpUtils;

    public function __construct(HttpKernelInterface $kernel, HttpUtils $httpUtils, $loginPath, $useForward = false) {
        $this->httpKernel = $kernel;
        $this->httpUtils = $httpUtils;
        $this->loginPath = $loginPath;
        $this->useForward = (Boolean) $useForward;

        parent::__construct($kernel, $httpUtils, $loginPath, $useForward);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request,
        AuthenticationException $authException = null) {

        if ($this->useForward) {
            $subRequest = $this->httpUtils->createRequest($request,
                $this->loginPath);

            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        $response = $this->httpUtils->createRedirectResponse($request, $this->loginPath);
        $url = $response->headers->get('location');
        $delim = strpos($url, '?') === False ? '?' : '&';
        $sid = session_name() . '=' . $request->getSession()->getId();
        $url .= $delim . $sid;

        return new RedirectResponse($url);
    }
}

