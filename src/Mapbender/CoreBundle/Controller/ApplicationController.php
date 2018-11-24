<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Asset\ApplicationAssetCache;
use Mapbender\CoreBundle\Asset\AssetFactory;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Mapbender;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Mapbender\WmsBundle\Entity\WmsSource;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

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
     * @Route("/application/{slug}/assets/{type}", requirements={"type" = "js|css|trans"})
     * @param Request $request
     * @param string $slug Application slug name
     * @param string $type Asset type
     * @return Response
     */
    public function assetsAction(Request $request, $slug, $type)
    {
        $isProduction = $this->isProduction();
        $cacheFile        = $this->getCachedAssetPath($slug, $type);
        $application = $this->getApplication($slug);

        $headers = array(
            'Content-Type' => $this->getMimeType($type),
        );

        $useCached = $isProduction && file_exists($cacheFile);
        if ($useCached) {
            $appEntity = $application->getEntity();
            $isAppDbBased = $appEntity->getSource() === ApplicationEntity::SOURCE_DB;
            $modificationTs = filectime($cacheFile);
            // Always reuse cache entry for YAML applications
            // Check the update timestamp only for DB applications,
            $useCached = !$isAppDbBased || ($appEntity->getUpdated()->getTimestamp() < $modificationTs);

            if ($useCached) {
                $response = new BinaryFileResponse($cacheFile, 200, $headers);
                // allow file timestamp to be read again correctly for 'Last-Modified' header
                clearstatcache();
                $response->isNotModified($request);
                return $response;
            }
        }

        $refs = $application->getAssetGroup($type);
        if ($type == "css") {
            /** @todo: use route to assets action, not REQUEST_URI, so this can move away from here */
            $sourcePath = $request->getBasePath() ?: '.';
            $factory = new AssetFactory($this->container, $refs, $request->server->get('REQUEST_URI'), $sourcePath);
            $content = $factory->compile();
        } else {
            $cache   = new ApplicationAssetCache($this->container, $refs);
            $assets  = $cache->fill();
            $content = $assets->dump();
        }

        if ($isProduction) {
            file_put_contents($cacheFile, $content);
            return new BinaryFileResponse($cacheFile, 200, $headers);
        } else {
            return new Response($content, 200, $headers);
        }
    }

    /**
     * Element action controller.
     *
     * Passes the request to
     * the element's httpAction.
     * @Route("/application/{slug}/element/{id}/{action}",
     *     defaults={ "id" = null, "action" = null },
     *     requirements={ "action" = ".+" })
     * @param Request $request
     * @param string $slug
     * @param string $id
     * @param string $action
     * @return Response
     *
     * @todo Symfony 3.x: update Element API to accept injected Request
     */
    public function elementAction(Request $request, $slug, $id, $action)
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
     * @param Request $request
     * @param string $slug Application
     * @return Response
     */
    public function applicationAction(Request $request, $slug)
    {
        $session      = $this->get("session");
        $application  = $this->getApplication($slug);
        $appEntity = $application->getEntity();
        // @todo: figure out why YAML applications should be excluded from html caching; they do use asset caching
        $useCache = $this->isProduction() && ($appEntity->getSource() === ApplicationEntity::SOURCE_DB);
        $session->set("proxyAllowed", true); // @todo: ...why?

        if ($useCache) {
            $cacheFile = $this->getCachedAssetPath($slug . "-" . session_id(), "html");
            $cacheValid = is_readable($cacheFile) && $appEntity->getUpdated()->getTimestamp() < filectime($cacheFile);
            if (!$cacheValid) {
                $content = $application->render();
                file_put_contents($cacheFile, $content);
                // allow file timestamp to be read again correctly for 'Last-Modified' header
                clearstatcache();
            }
            $response = new BinaryFileResponse($cacheFile);
            $response->isNotModified($request);
            return $response;
        } else {
            return new Response($application->render());
        }
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
     *
     * @Route("/application/{slug}/config")
     * @param string $slug
     * @return Response
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
        $user = $this->getUser();
        $application     = $application->getEntity();

        if ($application->isYamlBased()
            && count($application->getYamlRoles())
        ) {

            // If no token, then check manually if some role IS_AUTHENTICATED_ANONYMOUSLY
            if (!$user) {
                if (in_array('IS_AUTHENTICATED_ANONYMOUSLY', $application->getYamlRoles())) {
                    return;
                }
            }

            $passed = false;
            foreach ($application->getYamlRoles() as $role) {
                if ($this->isGranted($role)) {
                    $passed = true;
                    break;
                }
            }
            if (!$passed) {
                throw $this->createAccessDeniedException('You are not granted view permissions for this application.');
            }
        }
        $this->denyAccessUnlessGranted('VIEW', $application, 'You are not granted view permissions for this application.');

        if (!$application->isPublished()) {
            $this->denyAccessUnlessGranted('EDIT', $application, 'This application is not published at the moment');
        }
    }

    /**
     * Metadata action.
     *
     * @Route("/application/{slug}/metadata")
     * @param Request $request
     * @param string $slug
     * @return Response
     * @todo: param sourceId is required => it should be part of the route
     * @todo: param layerName is required => it should be part of the route
     * @todo: param slug is ignored; it should either go away, or be used to restrict possible instances to the Application's instances
     */
    public function metadataAction(Request $request, $slug)
    {
        $sourceId = $request->get("sourceId", null);
        if (!strlen($sourceId)) {
            throw new BadRequestHttpException();
        }
        $instance = $this->container->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($sourceId);
        if (!$instance) {
            throw new NotFoundHttpException();
        }
        /** @var SourceInstance $instance */
        if (!$this->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application'))) {
            $this->denyAccessUnlessGranted('VIEW', $instance->getLayerset()->getApplication());
        }
// TODO source access ?
//        $this->denyAccessUnlessGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source'));
//        $this->denyAccessUnlessGranted('VIEW', new $instance->getSource());

        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$instance->getManagertype()];
        return $this->forward($manager['bundle']. ':' . 'Repository:metadata', array(
            'sourceId' => $sourceId,
            'layerName' => $request->get('layerName', null)
        ));
    }

    /**
     * Get SourceInstances via tunnel
     * @see InstanceTunnelService
     *
     * @Route("/application/{slug}/instance/{instanceId}/tunnel")
     * @param Request $request
     * @param string $slug
     * @param string $instanceId
     * @return Response
     */
    public function instanceTunnelAction(Request $request, $slug, $instanceId)
    {
        // @todo: instance tunnel handling should move into a service component in WmsBundle
        /** @var \Mapbender\CoreBundle\Entity\SourceInstance $instance */
        $instance        = $this->container->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($instanceId);
        if (!$instance) {
            throw new NotFoundHttpException("No such instance");
        }
        if (!$this->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application'))) {
            $this->denyAccessUnlessGranted('VIEW', $instance->getLayerset()->getApplication());
        }

        /** @var WmsSource $source */
        $source = $instance->getSource();
// TODO source access ?
//        $this->denyAccessUnlessGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source'));
//        $this->denyAccessUnlessGranted('VIEW', $source);
        $headers     = array();
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

        Utils::setHeadersFromBrowserResponse($response, $browserResponse);
        foreach ($request->cookies as $key => $value) {
            $response->headers->removeCookie($key);
            $response->headers->setCookie(new Cookie($key, $value));
        }
        $response->setContent($browserResponse->getContent());
        return $response;
    }

    /**
     * @param $slug
     * @param $type
     * @return string
     */
    protected function getCachedAssetPath($slug, $type)
    {
        return $this->container->getParameter('kernel.cache_dir') . "/{$slug}.min.{$type}";
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

    /**
     * @return string
     */
    protected function getKernelEnvironment()
    {
        return $this->container->get('kernel')->getEnvironment();
    }

    /**
     * @return bool
     */
    protected function isProduction()
    {
        return $this->getKernelEnvironment() == "prod";
    }
}
