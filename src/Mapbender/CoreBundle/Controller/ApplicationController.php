<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Asset\ApplicationAssetCache;
use Mapbender\CoreBundle\Asset\AssetFactory;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Mapbender;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Mapbender\WmsBundle\Entity\WmsSource;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Application controller.
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class ApplicationController extends Controller
{

    /**
     * @return ConfigService
     */
    private function getConfigService()
    {
        /** @var ConfigService $presenter */
        $presenter = $this->get('mapbender.presenter.application.config.service');
        return $presenter;
    }

    /**
     * Asset controller.
     *
     * Dumps the assets for the given application and type. These are up to
     * date and this controller will be used during development mode.
     *
     * @Route("/application/{slug}/assets/{type}")
     * @param string $slug Application slug name
     * @param string $type Asset type
     * @return Response
     */
    public function assetsAction($slug, $type)
    {
        $response         = new Response();
        $request          = $this->getRequest();
        $env              = $this->container->get("kernel")->getEnvironment();
        $isProduction     = $env == "prod";
        $cacheFile        = $this->getCachedAssetPath($slug, $env, $type);
        $needCache        = $isProduction && !file_exists($cacheFile);
        $modificationDate = new \DateTime();
        $appEntity        = $this->get('mapbender')->getApplicationEntity($slug);

        $response->headers->set('Content-Type', $this->getMimeType($type));


        if ($isProduction && !$needCache) {
            $modificationTs = filectime($cacheFile);
            $isAppDbBased   = $appEntity->getSource() === ApplicationEntity::SOURCE_DB;
            $modificationDate->setTimestamp($modificationTs);

            if (!$isAppDbBased || ($isAppDbBased && $appEntity->getUpdated() < $modificationDate)) {
                $response->setLastModified($modificationDate);
                $response->headers->set('X-Asset-Modification-Time', $modificationDate->format('c'));
                if ($response->isNotModified($request)) {
                    return $response;
                }
                $response->setContent(file_get_contents($cacheFile));
                return $response;
            }
        }

        $application = $this->getApplication($slug);
        if ($type == "css") {
            $sourcePath = $request->getBasePath();
            $refs       = array_unique($application->getAssets('css'));
            $custom     = $application->getCustomCssAsset();
            if ($custom) {
                $refs[] = $custom;
            }
            $factory = new AssetFactory($this->container, $refs, 'css', $request->server->get('REQUEST_URI'), empty($sourcePath) ? "." : $sourcePath);
            $content = $factory->compile();
        } else {
            $cache   = new ApplicationAssetCache($this->container, $application->getAssets($type), $type);
            $assets  = $cache->fill($slug, 0);
            $content = $assets->dump();
        }

        if ($isProduction && $needCache) {
            file_put_contents($cacheFile, $content);
        }

        return $response->setContent($content);
    }

    /**
     * Element action controller.
     *
     * Passes the request to
     * the element's httpAction.
     * @Route("/application/{slug}/element/{id}/{action}",
     *     defaults={ "id" = null, "action" = null },
     *     requirements={ "action" = ".+" })
     */
    public function elementAction($slug, $id, $action)
    {
        $application = $this->getApplication($slug);
        $element     = $application->getElement($id);

        if(!$element){
            throw new NotFoundHttpException();
        }

        return $element->httpAction($action);
    }

    /**
     * Main application controller.
     *
     * @Route("/application/{slug}.{_format}", defaults={ "_format" = "html" })
     * @param string $slug Application
     * @return Response
     */
    public function applicationAction($slug)
    {
        $env          = $this->container->get("kernel")->getEnvironment();
        $isProduction = $env == "prod";
        $response     = new Response();
        $session      = $this->get("session");
        $application  = $this->getApplication($slug);

        if ($isProduction) {

            // Render YAML application
            if ($application->getEntity()->getSource() !== ApplicationEntity::SOURCE_DB) {
                $session->set("proxyAllowed", true);
                $response->setContent($application->render());
                return $response;
            }

            $cacheFile        = $this->getCachedAssetPath($slug . "-" . session_id(), $env, "html");
            $hasCache         = is_file($cacheFile);
            // If no cache or DB application is update, but cache is deprecated
            if (!$hasCache || $application->getEntity()->getUpdated()->getTimestamp() > filectime($cacheFile)) {
                $content = $application->render();
                file_put_contents($cacheFile, $content);
                $session->set("proxyAllowed", true);
                $response->setContent($content);

                // Update application and remove assets cache
                if($hasCache){
                    foreach(array(
                                $this->getCachedAssetPath($slug,$env,'css'),
                                $this->getCachedAssetPath($slug,$env,'js')) as $assetFileSrc){
                        if(is_file($assetFileSrc)){
                            unlink($assetFileSrc);
                        }
                    }
                }
            } else {
                $modificationDate = new \DateTime();
                $modificationDate->setTimestamp(filectime($cacheFile));
                $response->setLastModified($modificationDate);
                $response->headers->set('X-Asset-Modification-Time', $modificationDate->format('c'));
                if ($response->isNotModified($this->getRequest())) {
                    return $response;
                }
                $response->setContent(file_get_contents($cacheFile));
            }
        } else {
            $session->set("proxyAllowed", true);
            $response->setContent($application->render());
        }

        return $response;
    }

    /**
     * Get the application by slug.
     *
     * Tries to get the application with the given slug and throws an 404
     * exception if it can not be found. This also checks access control and
     * therefore may throw an AuthorizationException.
     *
     * @return Application
     */
    private function getApplication($slug)
    {
        /** @var Mapbender $mapbender */
        $mapbender = $this->get('mapbender');
        $application = $mapbender->getApplication($slug);

        if (!$application) {
            throw new NotFoundHttpException(
            'The application can not be found.');
        }

        $this->checkApplicationAccess($application);

        return $application;
    }

    /**
     * Main application controller.
     *
     * @Route("/application/{slug}/config")
     */
    public function configurationAction($slug)
    {
        $applicationEntity = $this->getApplication($slug)->getEntity();
        $this->get("session")->set("proxyAllowed", true);
        $configService = $this->getConfigService();
        $cacheService = $configService->getCacheService();
        $cacheKeyPath = array('config.json');
        $cachable = !!$this->container->getParameter('cachable.mapbender.application.config');
        if ($cachable) {
            $response = $cacheService->getResponse($applicationEntity, $cacheKeyPath, 'application/json');
        } else {
            $response = false;
        }
        if (!$cachable || !$response) {
            $freshConfig = $configService->getConfiguration($applicationEntity);
            $response = new JsonResponse($freshConfig);
            if ($cachable) {
                $cacheService->putValue($applicationEntity, $cacheKeyPath, $response->getContent());
            }
        }
        return $response;
    }

    /**
     * Check access permissions for given application.
     *
     * This will check if any ACE in the ACL for the given applications entity
     * grants the VIEW permission.
     *
     * @param Application $application
     */
    public function checkApplicationAccess(Application $application)
    {
        /** @var SecurityContext $securityContext */
        $securityContext = $this->get('security.context');
        $application     = $application->getEntity();

        if ($application->isYamlBased()
            && count($application->getYamlRoles())
        ) {

            // If no token, then check manually if some role IS_AUTHENTICATED_ANONYMOUSLY
            if (!$securityContext->getToken()) {
                if (in_array('IS_AUTHENTICATED_ANONYMOUSLY', $application->getYamlRoles())) {
                    return;
                }
            }

            $passed = false;
            foreach ($application->getYamlRoles() as $role) {
                if ($securityContext->isGranted($role)) {
                    $passed = true;
                    break;
                }
            }
            if (!$passed) {
                throw new AccessDeniedException('You are not granted view permissions for this application.');
            }
        }

        if (!$securityContext->isUserAllowedToView($application)) {
            throw new AccessDeniedException('You are not granted view permissions for this application.');
        }

        if (!$application->isPublished()
            && !$securityContext->isUserAllowedToEdit($application)
        ) {
            throw new AccessDeniedException('This application is not published at the moment');
        }
    }

    /**
     * Metadata controller.
     *
     * @Route("/application/{slug}/metadata")
     */
    public function metadataAction($slug)
    {
        $securityContext = $this->get('security.context');
        $sourceId = $this->container->get('request')->get("sourceId", null);
        $instance = $this->container->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($sourceId);
        if (!$securityContext->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application'))
            && !$securityContext->isGranted('VIEW', $instance->getLayerset()->getApplication())) {
            throw new AccessDeniedException();
        }
// TODO source access ?
//        if (!$securityContext->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source'))
//            && !$securityContext->isGranted('VIEW', $instance->getSource())) {
//            throw new AccessDeniedException();
//        }

        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$instance->getManagertype()];

        $path = array('_controller' => $manager['bundle'] . ":" . "Repository:metadata");
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle(
                $subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Get SourceInstances via HTTP Basic Authentication
     *
     * @Route("/application/{slug}/instance/{instanceId}/tunnel")
     */
    public function instanceTunnelAction($slug, $instanceId)
    {
        // @todo: instance tunnel handling should move into a service component in WmsBundle
        /** @var \Mapbender\CoreBundle\Entity\SourceInstance $instance */
        $instance        = $this->container->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($instanceId);
        if (!$instance) {
            throw new NotFoundHttpException("No such instance");
        }

        $securityContext = $this->get('security.context');

        if (!$securityContext->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application'))
            && !$securityContext->isGranted('VIEW', $instance->getLayerset()->getApplication())) {
            throw new AccessDeniedException();
        }

        /** @var WmsSource $source */
        $source = $instance->getSource();
// TODO source access ?
//        if (!$securityContext->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source'))
//            && !$securityContext->isGranted('VIEW', $instance->getSource())) {
//            throw new AccessDeniedException();
//        }
//        $params = $this->getRequest()->getMethod() == 'POST' ?
//            $this->get("request")->request->all() : $this->get("request")->query->all();
        $headers     = array();
        /** @var Request $request */
        $request = $this->get("request");
        $postParams  = $request->request->all();
        $getParams   = $request->query->all();
        $user        = $source->getUsername() ? $source->getUsername() : null;
        $password    = $source->getUsername() ? $source->getPassword() : null;
        $instHandler = SourceInstanceEntityHandler::createHandler($this->container, $instance);
        $vendorspec  = $instHandler->getSensitiveVendorSpecific();
        /* overwrite vendorspecific parameters from handler with get/post parameters */
        if (count($getParams)) {
            $getParams = array_merge($vendorspec, $getParams);
        }
        if (count($postParams)) {
            $postParams = array_merge($vendorspec, $postParams);
        }
        $proxy_config = $this->container->getParameter("owsproxy.proxy");

        $requestType = RequestUtil::getGetParamCaseInsensitive($request, 'request', null);
        if (!$requestType) {
            throw new BadRequestHttpException('Missing mandatory parameter `request` in tunnelAction');
        }
        /** @var InstanceTunnelService $tunnelService */
        $tunnelService = $this->get('mapbender.source.instancetunnel.service');
        $instanceTunnel = $tunnelService->makeEndpoint($instance);
        $url = $instanceTunnel->getInternalUrl($request);
        if (!$url) {
            throw new NotFoundHttpException('Operation "' . $requestType . '" is not supported by "tunnelAction".');
        }

        $proxy_query     = ProxyQuery::createFromUrl($url, $user, $password, $headers, $getParams, $postParams);
        $proxy           = new CommonProxy($proxy_config, $proxy_query);
        $browserResponse = $proxy->handle();
        $response        = new Response();

        $cookies_req = $this->get("request")->cookies;
        Utils::setHeadersFromBrowserResponse($response, $browserResponse);
        foreach ($cookies_req as $key => $value) {
            $response->headers->removeCookie($key);
            $response->headers->setCookie(new Cookie($key, $value));
        }
        $response->setContent($browserResponse->getContent());
        return $response;
    }

    /**
     * @param $slug
     * @param $env
     * @param $type
     * @return string
     */
    public function getCachedAssetPath($slug, $env, $type)
    {
        return $this->container->getParameter('kernel.root_dir') . "/cache/" . $env . "/" . $slug . ".min." . $type;
    }

    /**
     * Get mime type
     *
     * @param string $type
     * @return array
     */
    protected function getMimeType($type)
    {
        static $types = array(
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'trans' => 'application/javascript');
        return $types[$type];
    }
}
