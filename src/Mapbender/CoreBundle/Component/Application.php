<?php
namespace Mapbender\CoreBundle\Component;

use Doctrine\Common\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Mapbender\CoreBundle\Component\Presenter\ApplicationService;
use Mapbender\CoreBundle\Controller\ApplicationController;
use Mapbender\CoreBundle\Entity\Application as Entity;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Collection of servicy behaviors related to application
 *
 * This class is getting gradually dissolved into a collection of true services that can be replugged and extended
 * via DI.
 * For asset collection and compilation @see \Mapbender\CoreBundle\Asset\ApplicationAssetService
 * For client-facing configuration emission @see \Mapbender\CoreBundle\Component\Presenter\Application\ConfigService
 * For Yaml application permissions setup @see \Mapbender\CoreBundle\Component\YamlApplicationImporter
 * For creating / cloning / destroying uploads directories @see \Mapbender\CoreBundle\Component\UploadsManager
 *
 * @deprecated
 * @internal
 * @author Christian Wygoda
 */
class Application
{
    /**
     * @var ContainerInterface $container The container
     */
    protected $container;

    /**
     * @var Template $template The application template class
     */
    protected $template;

    /**
     * @var Element[][] $element lists by region
     */
    protected $elements;

    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @param ContainerInterface $container The container
     * @param Entity             $entity    The configuration entity
     */
    public function __construct(ContainerInterface $container, Entity $entity)
    {
        $this->container = $container;
        $this->entity    = $entity;
    }

    /**
     * Get the configuration entity.
     *
     * @return Entity $entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Get the application ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Get the application slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->entity->getSlug();
    }

    /**
     * Get the application title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->entity->getTitle();
    }

    /**
     * Get the application description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->entity->getDescription();
    }

    /*************************************************************************
     *                                                                       *
     *                              Frontend stuff                           *
     *                                                                       *
     *************************************************************************/

    /**
     * Render the application
     *
     * @return string $html The rendered HTML
     */
    public function render()
    {
        $template = $this->getTemplate();
        return $template->render();
    }

    /**
     * @return ConfigService
     */
    private function getConfigService()
    {
        /** @var ConfigService $presenter */
        $presenter = $this->container->get('mapbender.presenter.application.config.service');
        return $presenter;
    }

    /**
     * Get the Application configuration (application, elements, layers) as a json-encoded string.
     *
     * @return string Configuration as JSON string
     * @deprecated This method is only called from (copies of) the mobile.html.twig application template
     *     In modern Mapbender templates, Application configuration is loaded by a separate Ajax route,
     *     completely independent of the twig template. Simply removing the script fragment that ends up
     *     calling this method from your twig template will automatically switch to Ajax config loading,
     *     doesn't require writing any replacement logic, and removes the warning.
     * @see ApplicationController::configurationAction()
     */
    public function getConfiguration()
    {
        @trigger_error("Deprecated: Inlining Application configuration data into your template is unnecessary. "
                     . "Please remove the 'Mapbender.configuration = {{ application.configuration | raw }};' script "
                     . "fragment from your Application twig template.", E_USER_DEPRECATED);
        $configService = $this->getConfigService();
        $configuration = $configService->getConfiguration($this->entity);
        return json_encode((object)$configuration);
    }

    /**
     * Get template object
     *
     * @return Template
     */
    public function getTemplate()
    {
        if (!$this->template) {
            $template       = $this->entity->getTemplate();
            $this->template = new $template($this->container, $this);
        }
        return $this->template;
    }

    /**
     * Get region elements, optionally by region.
     * This called almost exclusively from twig templates, with or without the region paraemter.
     *
     * @param string $regionName deprecated; Region to get elements for. If null, all elements  are returned.
     * @return Element[][] keyed by region name (string)
     */
    public function getElements($regionName = null)
    {
        $appService = $this->getService();
        if ($this->elements === null) {
            $activeElements = $appService->getActiveElements($this->entity, true);
            $this->elements = array();
            foreach ($activeElements as $elementComponent) {
                $elementRegion = $elementComponent->getEntity()->getRegion();
                if (!array_key_exists($elementRegion, $this->elements)) {
                    $this->elements[$elementRegion] = array();
                }
                $this->elements[$elementRegion][] = $elementComponent;
            }
        }
        if ($regionName) {
            return ArrayUtil::getDefault($this->elements, $regionName, array());
        } else {
            return $this->elements;
        }
    }

    /**
     * Checks and generates a valid slug.
     *
     * @param ContainerInterface $container container
     * @param string             $slug      slug to check
     * @param string             $suffix
     * @return string a valid generated slug
     */
    public static function generateSlug($container, $slug, $suffix = 'copy')
    {
        $application = $container->get('mapbender')->getApplicationEntity($slug);
        if (!$application) {
            return $slug;
        } else {
            $count = 0;
        }
        /** @var ObjectRepository $rep */
        $rep = $container->get('doctrine')->getRepository('MapbenderCoreBundle:Application');
        do {
            $copySlug = $slug . "_" . $suffix . ($count > 0 ? '_' . $count : '');
            $count++;
        } while ($rep->findOneBy(array('slug' => $copySlug)));
        return $copySlug;
    }

    /**
     * Returns the public "uploads" directory.
     * NOTE: this has nothing to with applications. Some legacy usages passed in an application
     * slug as a second argument, but it was only ever evaluated as a boolean.
     *
     * @param ContainerInterface $container Container
     * @param bool               $webRelative
     * @return string the path to uploads dir or null.
     * @deprecated use the uploads_manager service
     */
    public static function getUploadsDir($container, $webRelative = false)
    {
        $ulm = self::getServiceStatic($container)->getUploadsManager();
        try {

            if ($webRelative) {
                return $ulm->getWebRelativeBasePath(true);
            } else {
                return $ulm->getAbsoluteBasePath(true);
            }
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Returns the web-relative path to the application's uploads directory.
     *
     * @param ContainerInterface $container Container
     * @param string             $slug      application's slug
     * @return boolean true if the application's directory already existed or has just been successfully created
     * @deprecated use the uploads_manager service
     */
    public static function getAppWebDir($container, $slug)
    {
        $ulm = static::getServiceStatic($container)->getUploadsManager();
        try {
            $ulm->getSubdirectoryPath($slug, true);
            return $ulm->getWebRelativeBasePath(false) . '/' . $slug;
        } catch (IOException $e) {
            return null;
        }
    }

    /**
     * Returns an url to application's public directory.
     *
     * @param ContainerInterface $container Container
     * @param string             $slug      application's slug
     * @return string a url to wmc directory or to file with "$filename"
     */
    public static function getAppWebUrl($container, $slug)
    {
        return Application::getUploadsUrl($container) . "/" . $slug;
    }

    /**
     * Returns an url to public "uploads" directory.
     *
     * @param ContainerInterface $container Container
     * @return string an url to public "uploads" directory
     */
    public static function getUploadsUrl($container)
    {
        $base_url = Application::getBaseUrl($container);
        return $base_url . '/' . Application::getUploadsDir($container, true);
    }

    /**
     * Returns a base url.
     *
     * @param ContainerInterface $container Container
     * @return string a base url
     */
    public static function getBaseUrl($container)
    {
        $request = $container->get('request_stack')->getCurrentRequest();
        return $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
    }

    /**
     * @return ApplicationService
     */
    protected function getService()
    {
        /** @var ApplicationService $service */
        $service = $this->container->get('mapbender.presenter.application.service');
        return $service;
    }

    /**
     * @param ContainerInterface $container
     * @return ApplicationService
     */
    private static function getServiceStatic(ContainerInterface $container)
    {
        /** @var ApplicationService $service */
        $service = $container->get('mapbender.presenter.application.service');
        return $service;
    }
}
