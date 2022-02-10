<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Controller\ApplicationControllerBase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
    /** @var TranslatorInterface */
    protected $translator;
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;
    /** @var ElementInventoryService */
    protected $elementInventory;
    /** @var UploadsManager */
    protected $uploadsManager;
    protected $fileCacheDirectory;
    protected $forcedMapEngine;
    protected $isDebug;

    public function __construct(TranslatorInterface $translator,
                                ApplicationYAMLMapper $yamlRepository,
                                ElementInventoryService $elementInventory,
                                UploadsManager $uploadsManager,
                                $fileCacheDirectory,
                                $forcedMapEngine,
                                $isDebug)
    {
        $this->translator = $translator;
        $this->yamlRepository = $yamlRepository;
        $this->elementInventory = $elementInventory;
        $this->uploadsManager = $uploadsManager;
        $this->fileCacheDirectory = $fileCacheDirectory;
        $this->forcedMapEngine = $forcedMapEngine;
        $this->isDebug = $isDebug;
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
        if ($this->forcedMapEngine) {
            $appEntity->setMapEngineCode($this->forcedMapEngine);
        }

        $headers = array(
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'max-age=0, must-revalidate, private',
        );

        if (!$this->isDebug) {
            $cacheFile = $this->getCachePath($request, $appEntity);
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
     * @param Application $application
     * @return string
     */
    protected function renderApplication(Application $application)
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
     * @return Application
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    private function getApplicationEntity($slug)
    {
        /** @var Application|null $application */
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
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
     * @param Request $request
     * @param Application $application
     * @return string
     */
    protected function getCachePath(Request $request, Application $application)
    {
        // Output depends on locale and base url => bake into cache key
        // 16 bits of entropy should be enough to distinguish '', 'app.php' and 'app_dev.php'
        $baseUrlHash = substr(md5($request->getBaseUrl()), 0, 4);
        $locale = $this->translator->getLocale();
        // Output also depends on user (granted elements may vary)
        $user = $this->getUser();
        $isAnon = !$user || !\is_object($user) || !($user instanceof UserInterface);
        // @todo: DO NOT use a user-specific cache location (=session_id). This completely defeates the purpose of caching.
        if ($isAnon) {
            $userMarker = 'anon';
        } else {
            $request->getSession()->start();
            $userMarker = $request->getSession()->getId();
        }

        $name = "{$application->getSlug()}.{$locale}.{$userMarker}.{$baseUrlHash}.{$application->getMapEngineCode()}.html";
        return $this->fileCacheDirectory . "/{$name}";
    }

    /**
     * @param Application $application
     * @return string|null
     */
    protected function getPublicUploadsBaseUrl(Application $application)
    {
        $slug = $application->getSlug();
        try {
            $this->uploadsManager->getSubdirectoryPath($slug, true);
            return $this->uploadsManager->getWebRelativeBasePath(false) . '/' . $slug;
        } catch (IOException $e) {
            return null;
        }
    }
}
