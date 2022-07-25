<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ApplicationMarkupCache
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    /** @var LocaleAwareInterface */
    protected $localeProvider;
    protected $basePath;
    protected $isDebug;

    public function __construct(TokenStorageInterface $tokenStorage,
                                LocaleAwareInterface $localeProvider,
                                $basePath)
    {
        $this->tokenStorage = $tokenStorage;
        $this->localeProvider = $localeProvider;
        $this->basePath = $basePath;
    }

    /**
     * @param Request $request
     * @param Application $application
     * @param ApplicationMarkupRenderer $renderer
     * @return Response
     */
    public function getMarkupResponse(Request $request, Application $application, ApplicationMarkupRenderer $renderer)
    {
        $filePath = $this->getFilePath($request, $application);
        $cacheValid = \is_readable($filePath) && $application->getUpdated()->getTimestamp() < filectime($filePath);
        if ($cacheValid) {
            $response = new BinaryFileResponse($filePath);
            $response->isNotModified($request);
        } else {
            $html = $renderer->renderApplication($application);
            \file_put_contents($filePath, $html);
            // allow file timestamp to be read again correctly for 'Last-Modified' header
            \clearstatcache($filePath, true);
            $response = new Response($html);
        }
        $response->setVary(array(
            'Accept-Language',
            // Bust browser cache on session / login state change
            'Cookie',
        ));
        return $response;
    }

    /**
     * @param Request $request
     * @param Application $application
     * @return string
     */
    protected function getFilePath(Request $request, Application $application)
    {
        // Output depends on locale and base url => bake into cache key
        // 16 bits of entropy should be enough to distinguish '', 'app.php' and 'app_dev.php'
        $baseUrlHash = substr(md5($request->getBaseUrl()), 0, 4);
        $locale = $this->localeProvider->getLocale();
        $token = $this->tokenStorage->getToken();
        $isAnon = !$token || ($token instanceof AnonymousToken);
        // Output also depends on user (granted elements may vary)
        // @todo: DO NOT use a user-specific cache location (=session_id). This completely defeates the purpose of caching.
        if ($isAnon) {
            $userMarker = 'anon';
        } else {
            $request->getSession()->start();
            $userMarker = $request->getSession()->getId();
        }

        $name = "{$application->getSlug()}.{$locale}.{$userMarker}.{$baseUrlHash}.{$application->getMapEngineCode()}.html";
        return $this->basePath . "/{$name}";
    }
}
