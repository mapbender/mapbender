<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Controller\ApplicationControllerBase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    protected $fileCacheDirectory;
    protected $isDebug;

    /** @var ElementInventoryService */
    protected $elementInventory;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                ElementInventoryService $elementInventory,
                                $fileCacheDirectory,
                                $isDebug)
    {
        $this->yamlRepository = $yamlRepository;
        $this->elementInventory = $elementInventory;
        $this->fileCacheDirectory = $fileCacheDirectory;
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
        $headers = array(
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'max-age=0, must-revalidate, private',
        );

        if (!$this->isDebug) {
            $cacheFile = $this->getCachePath($request, $slug);
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
     * @param string $slug
     * @return string
     */
    protected function getCachePath(Request $request, $slug)
    {
        // Output depends on locale and base url => bake into cache key
        // 16 bits of entropy should be enough to distinguish '', 'app.php' and 'app_dev.php'
        $baseUrlHash = substr(md5($request->getBaseUrl()), 0, 4);
        $locale = $this->getTranslator()->getLocale();
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

        $name = "{$slug}-{$userMarker}.min.{$baseUrlHash}.{$locale}.html";
        return $this->fileCacheDirectory . "/{$name}";
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
