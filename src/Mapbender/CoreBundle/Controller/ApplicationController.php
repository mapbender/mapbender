<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Asset\ApplicationAssetService;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\ElementHttpHandlerInterface;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Mapbender\CoreBundle\Component\Presenter\ApplicationService;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Mapbender;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        $appEntity = $this->getApplicationEntity($slug);

        $headers = array(
            'Content-Type' => $this->getMimeType($type),
        );

        $useCached = $isProduction && file_exists($cacheFile);
        if ($useCached) {
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
        /** @var ApplicationAssetService $assetService */
        $assetService = $this->container->get('mapbender.application_asset.service');
        $content = $assetService->getAssetContent($appEntity, $type);

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
     * the element's handleHttpRequest.
     * @Route("/application/{slug}/element/{id}/{action}",
     *     defaults={ "id" = null, "action" = null },
     *     requirements={ "action" = ".+" })
     * @param Request $request
     * @param string $slug
     * @param string $id
     * @param string $action
     * @return Response
     */
    public function elementAction(Request $request, $slug, $id, $action)
    {
        $application = $this->getApplicationEntity($slug);
        /** @var ApplicationService $appService */
        $appService = $this->get('mapbender.presenter.application.service');
        $elementComponent = $appService->getSingleElementComponent($application, $id);
        if (!$elementComponent || !$elementComponent instanceof ElementHttpHandlerInterface) {
            throw new NotFoundHttpException();
        }
        return $elementComponent->handleHttpRequest($request);
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
        $headers = array(
            'Content-Type' => 'text/html; charset=UTF-8',
        );
        if ($useCache) {
            $cacheFile = $this->getCachedAssetPath($slug . "-" . session_id(), "html");
            $cacheValid = is_readable($cacheFile) && $appEntity->getUpdated()->getTimestamp() < filectime($cacheFile);
            if (!$cacheValid) {
                $content = $application->render();
                file_put_contents($cacheFile, $content);
                // allow file timestamp to be read again correctly for 'Last-Modified' header
                clearstatcache();
            }
            $response = new BinaryFileResponse($cacheFile, 200, $headers);
            $response->isNotModified($request);
            return $response;
        } else {
            return new Response($application->render(), 200, $headers);
        }
    }

    /**
     * Get the application component by slug.
     *
     * Checks existance and grants and throws accordingly.
     *
     * @param string $slug
     * @return Application
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     */
    private function getApplication($slug)
    {
        $entity = $this->getApplicationEntity($slug);
        return new Application($this->container, $entity);
    }

    /**
     * @param string $slug
     * @return ApplicationEntity
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     */
    private function getApplicationEntity($slug)
    {
        /** @var Mapbender $mapbender */
        $mapbender = $this->get('mapbender');
        $entity = $mapbender->getApplicationEntity($slug);

        if (!$entity) {
            throw new NotFoundHttpException('The application can not be found.');
        }
        $this->checkApplicationAccess($entity);
        return $entity;
    }

    /**
     *
     * @Route("/application/{slug}/config")
     * @param string $slug
     * @return Response
     */
    public function configurationAction($slug)
    {
        $applicationEntity = $this->getApplicationEntity($slug);
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
     * @param ApplicationEntity $application
     */
    private function checkApplicationAccess(ApplicationEntity $application)
    {
        $user = $this->getUser();

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

        $layerId = $request->query->get('layerId', null);
        $metadata  = $instance->getMetadata();
        if (!$metadata) {
            throw new NotFoundHttpException();
        }
        $metadata->setContenttype(SourceMetadata::$CONTENTTYPE_ELEMENT);
        $metadata->setContainer(SourceMetadata::$CONTAINER_ACCORDION);
        $template = $metadata->getTemplate();
        $content = $this->renderView($template, $metadata->getData($instance, $layerId));
        return  new Response($content, 200, array(
            'Content-Type' => 'text/html',
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
        $instanceTunnel = $this->getGrantedTunnelEndpoint($instanceId, $slug);
        $requestType = RequestUtil::getGetParamCaseInsensitive($request, 'request', null);
        if (!$requestType) {
            throw new BadRequestHttpException('Missing mandatory parameter `request` in tunnelAction');
        }
        $url = $instanceTunnel->getService()->getInternalUrl($request, false);
        if ($this->container->getParameter('kernel.debug') && $request->query->has('reveal-internal')) {
            return new Response($url);
        }

        if (!$url) {
            throw new NotFoundHttpException('Operation "' . $requestType . '" is not supported by "tunnelAction".');
        }

        return $instanceTunnel->getUrl($url);
    }

    /**
     * Get a layer's legend image via tunnel
     * @see InstanceTunnelService
     *
     * @Route("/application/{slug}/instance/{instanceId}/tunnel/legend/{layerId}")
     * @param Request $request
     * @param string $slug
     * @param string $instanceId
     * @param string $layerId
     * @return Response
     */
    public function instanceTunnelLegendAction(Request $request, $slug, $instanceId, $layerId)
    {
        $instanceTunnel = $this->getGrantedTunnelEndpoint($instanceId, $slug);
        $url = $instanceTunnel->getService()->getInternalUrl($request, false);
        if (!$url) {
            throw $this->createNotFoundException();
        }
        if ($this->container->getParameter('kernel.debug') && $request->query->has('reveal-internal')) {
            return new Response($url);
        } else {
            return $instanceTunnel->getUrl($url);
        }
    }

    /**
     * @param string $instanceId
     * @param string $applicationSlug
     * @return \Mapbender\CoreBundle\Component\Source\Tunnel\Endpoint
     */
    protected function getGrantedTunnelEndpoint($instanceId, $applicationSlug)
    {
        /** @var \Mapbender\CoreBundle\Entity\SourceInstance $instance */
        $instance = $this->getDoctrine()
            ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($instanceId);
        if (!$instance) {
            throw new NotFoundHttpException("No such instance");
        }
        // Deny forged cross-requests to an instance that doesn't belong to this application
        $application = $instance->getLayerset()->getApplication();
        if ($application->getSlug() !== $applicationSlug) {
            throw new NotFoundHttpException("No such instance");
        }
        if (!$this->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application'))) {
            $this->denyAccessUnlessGranted('VIEW', $application);
        }
        /** @var InstanceTunnelService $tunnelService */
        $tunnelService = $this->get('mapbender.source.instancetunnel.service');
        return $tunnelService->makeEndpoint($instance);
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
