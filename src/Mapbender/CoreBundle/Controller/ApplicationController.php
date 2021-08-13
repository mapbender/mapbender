<?php

namespace Mapbender\CoreBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Mapbender\Component\Application\TemplateAssetDependencyInterface;
use Mapbender\CoreBundle\Asset\ApplicationAssetService;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Mapbender\ManagerBundle\Controller\ApplicationControllerBase;
use Mapbender\ManagerBundle\Template\LoginTemplate;
use Mapbender\ManagerBundle\Template\ManagerTemplate;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Application controller.
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class ApplicationController extends ApplicationControllerBase
{
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;
    protected $containerTimestamp;
    protected $fileCacheDirectory;
    protected $enableConfigCache;
    protected $isDebug;

    /** @var ElementInventoryService */
    protected $elementInventory;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                ElementInventoryService $elementInventory,
                                $containerTimestamp,
                                $fileCacheDirectory,
                                $enableConfigCache,
                                $isDebug)
    {
        $this->yamlRepository = $yamlRepository;
        $this->elementInventory = $elementInventory;
        $this->containerTimestamp = intval(ceil($containerTimestamp));
        $this->enableConfigCache = $enableConfigCache;
        $this->fileCacheDirectory = $fileCacheDirectory;
        $this->isDebug = $isDebug;
    }

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
     * @param string $slug of Application
     * @param string $type one of 'css', 'js' or 'trans'
     * @return Response
     */
    public function assetsAction(Request $request, $slug, $type)
    {
        $cacheFile = $this->getCachedAssetPath($request, $slug, $type);
        if ($source = $this->getManagerAssetDependencies($slug)) {
            // @todo: TBD more reasonable criteria of backend / login asset cachability
            $appModificationTs =$this->containerTimestamp;
        } else {
            $source = $this->getApplicationEntity($slug);
            $appModificationTs = $source->getUpdated()->getTimestamp();
        }
        $headers = array(
            'Content-Type' => $this->getMimeType($type),
            'Cache-Control' => 'max-age=0, must-revalidate, private',
        );

        $useCached = (!$this->isDebug) && file_exists($cacheFile);
        if ($useCached && $appModificationTs < filectime($cacheFile)) {
            $response = new BinaryFileResponse($cacheFile, 200, $headers);
            // allow file timestamp to be read again correctly for 'Last-Modified' header
            clearstatcache($cacheFile, true);
            $response->isNotModified($request);
            return $response;
        }
        /** @var ApplicationAssetService $assetService */
        $assetService = $this->container->get('mapbender.application_asset.service');
        if ($source instanceof ApplicationEntity) {
            $content = $assetService->getAssetContent($source, $type);
        } else {
            $content = $assetService->getBackendAssetContent($source, $type);
        }

        if (!$this->isDebug) {
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
        $element = $application->getElements()->matching(Criteria::create()->where(Criteria::expr()->eq('id', $id)))->first();
        if (!$element) {
            throw new NotFoundHttpException();
        }
        /** @todo Sf4: use DI for service access */
        /** @var ElementFilter $filter */
        $filter = $this->get('mapbender.element_filter');
        if (!$filter->prepareFrontend(array($element), true, false)) {
            throw new NotFoundHttpException();
        }
        $handler = $this->elementInventory->getHttpHandler($element);
        if ($handler) {
            return $handler->handleRequest($element, $request);
        } else {
            throw new NotFoundHttpException();
        }
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
        $appEntity = $this->getApplicationEntity($slug);
        $headers = array(
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'max-age=0, must-revalidate, private',
        );

        if (!$this->isDebug) {
            $user = $this->getUser();
            $isAnon = !$user || !\is_object($user) || !($user instanceof UserInterface);
            // @todo: DO NOT use a user-specific cache location (=session_id). This completely defeates the purpose of caching.
            if ($isAnon) {
                $cacheFile = $this->getCachedAssetPath($request, $slug . '-anon', "html");
            } else {
                $request->getSession()->start();
                $sessionId = $request->getSession()->getId();
                $cacheFile = $this->getCachedAssetPath($request, $slug . "-" . $sessionId, "html");
            }
            $cacheValid = is_readable($cacheFile) && $appEntity->getUpdated()->getTimestamp() < filectime($cacheFile);
            if (!$cacheValid) {
                $content = $this->renderApplication($appEntity);
                file_put_contents($cacheFile, $content);
                // allow file timestamp to be read again correctly for 'Last-Modified' header
                clearstatcache($cacheFile, true);
            }
            $response = new BinaryFileResponse($cacheFile, 200, $headers);
            $response->isNotModified($request);
            return $response;
        } else {
            return new Response($this->renderApplication($appEntity), 200, $headers);
        }
    }

    /**
     * @param ApplicationEntity $application
     * @return string
     */
    protected function renderApplication(ApplicationEntity $application)
    {
        /** @var string|Template $templateCls */
        $templateCls = $application->getTemplate();
        /** @var Template $templateObj */
        $templateObj = new $templateCls();
        $twigTemplate = $templateObj->getTwigTemplate();
        $vars = array_replace($templateObj->getTemplateVars($application), array(
            'application' => $application,
            'uploads_dir' => $this->getPublicUploadsBaseUrl($application),
            'body_class' => $templateObj->getBodyClass($application),
        ));
        return $this->renderView($twigTemplate, $vars);
    }

    /**
     * @param string $slug
     * @return ApplicationEntity
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    private function getApplicationEntity($slug)
    {
        /** @var ApplicationEntity|null $application */
        $application = $this->getDoctrine()->getRepository(ApplicationEntity::class)->findOneBy(array(
            'slug' => $slug,
        ));
        $application = $application ?: $this->yamlRepository->getApplication($slug);
        if (!$application) {
            throw new NotFoundHttpException();
        }
        $this->denyAccessUnlessGranted('VIEW', $application);
        return $application;
    }

    /**
     * @param string $slug
     * @return TemplateAssetDependencyInterface|null
     */
    private function getManagerAssetDependencies($slug)
    {
        switch ($slug) {
            case 'manager':
                return new ManagerTemplate();
            case 'mb3-login':
                return new LoginTemplate();
            default:
                return null;
        }
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
        $configService = $this->getConfigService();
        $cacheService = $configService->getCacheService();
        $cacheKeyPath = array('config.json');

        if ($this->enableConfigCache) {
            $response = $cacheService->getResponse($applicationEntity, $cacheKeyPath, 'application/json');
        } else {
            $response = false;
        }
        if (!$this->enableConfigCache || !$response) {
            $freshConfig = $configService->getConfiguration($applicationEntity);
            $response = new JsonResponse($freshConfig);
            if ($this->enableConfigCache) {
                $cacheService->putValue($applicationEntity, $cacheKeyPath, $response->getContent());
            }
        }
        return $response;
    }

    /**
     * Metadata action.
     *
     * @Route("/application/metadata/{instance}/{layerId}")
     * @param SourceInstance $instance
     * @param string $layerId
     * @return Response
     */
    public function metadataAction(SourceInstance $instance, $layerId)
    {
        // NOTE: cannot work for Yaml applications because Yaml-applications don't have source instances in the database
        // @todo: give Yaml applications a proper object repository and make this work
        $metadata  = $instance->getMetadata();
        if (!$metadata) {
            throw new NotFoundHttpException();
        }
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
        if ($this->isDebug && $request->query->has('reveal-internal')) {
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
        if ($this->isDebug && $request->query->has('reveal-internal')) {
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
        /** @var SourceInstance|null $instance */
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
     * @param Request $request
     * @param string $slug
     * @param string $type
     * @return string
     */
    protected function getCachedAssetPath(Request $request, $slug, $type)
    {
        $localeDependent = array(
            'html',
            'trans',
        );
        if (in_array($type, $localeDependent)) {
            $extension = $this->getTranslator()->getLocale() . ".{$type}";
        } else {
            $extension = $type;
        }
        $baseUrlDependent = array(
            'html',
            'css',
        );
        if (in_array($type, $baseUrlDependent)) {
            // 16 bits of entropy should be enough to distinguish '', 'app.php' and 'app_dev.php'
            $baseUrlHash = substr(md5($request->getBaseUrl()), 0, 4);
            $extension = "{$baseUrlHash}.{$extension}";
        }
        return $this->fileCacheDirectory . "/{$slug}.min.{$extension}";
    }

    /**
     * Get mime type
     *
     * @param string $type
     * @return string
     */
    protected function getMimeType($type)
    {
        switch ($type) {
            case 'js':
            case 'trans':
                return 'application/javascript';
            case 'css':
                return 'text/css';
            default:
                // Uh-oh
                return null;
        }
    }

    /**
     * @return bool
     */
    protected function isProduction()
    {
        return !$this->isDebug;
    }

    /**
     * @param ApplicationEntity $application
     * @return string|null
     */
    protected function getPublicUploadsBaseUrl(ApplicationEntity $application)
    {
        $ulm = $this->getUploadsManager();
        $slug = $application->getSlug();
        try {
            $ulm->getSubdirectoryPath($slug, true);
            return $ulm->getWebRelativeBasePath(false) . '/' . $slug;
        } catch (IOException $e) {
            return null;
        }
    }
}
