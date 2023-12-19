<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ApplicationMarkupCache
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    /** @var AccessDecisionManagerInterface */
    protected $accessDecisionManager;
    /** @var LocaleAwareInterface */
    protected $localeProvider;
    protected $basePath;
    protected $isDebug;

    protected bool $includeSessionId = false;

    public function __construct(TokenStorageInterface          $tokenStorage,
                                AccessDecisionManagerInterface $accessDecisionManager,
                                LocaleAwareInterface           $localeProvider,
                                                               $basePath,
                                bool                           $includeSessionId)
    {
        $this->accessDecisionManager = $accessDecisionManager;
        $this->tokenStorage = $tokenStorage;
        $this->localeProvider = $localeProvider;
        $this->basePath = $basePath;
        $this->includeSessionId = $includeSessionId;
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
        // Output depends on
        // 1) locale
        // 2) Base url (app.php / app_dev.php / nothing); generated script / asset urls may differ
        // 3) Granted element subset
        // => Bake all of these into cache file path.
        $parts = array(
            $application->getSlug(),
            $this->localeProvider->getLocale(),
            $application->getMapEngineCode(),
        );

        // Output also depends on user (granted elements may vary)
        // @todo: DO NOT use a user-specific cache location (=session_id). This completely defeates the purpose of caching.
        $hashParts = array(
            $request->getBaseUrl(),
        );
        $token = $this->tokenStorage->getToken();
        $isAnon = !$token || ($token instanceof AnonymousToken);
        if ($isAnon) {
            // All anons will have the same grant check results. We can skip it, but we need to
            // make sure this cache entry is only used by anons.
            $parts[] = 'anon';
            // Add base url hash. 16 bits of entropy should be enough for three possible base urls.
            $parts[] = \substr(\md5($request->getBaseUrl()), 0, 4);
        } else {
            $hashParts = array(
                $request->getBaseUrl(),
            );
            foreach ($application->getElements() as $element) {
                if (!$this->accessDecisionManager->decide($token, array('VIEW'), $element)) {
                    $hashParts[] = $element->getId();
                }
            }
            $parts[] = \md5(implode(';', $hashParts));

            if ($this->includeSessionId) {
                $parts[] = $request->getSession()->getId();
            }
        }
        $name = \implode('.', $parts) . '.html';
        return $this->basePath . "/{$name}";
    }
}
