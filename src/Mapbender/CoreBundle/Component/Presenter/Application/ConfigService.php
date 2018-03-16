<?php

namespace Mapbender\CoreBundle\Component\Presenter\Application;

use Mapbender\CoreBundle\Component\Element as Element;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Mapbender\CoreBundle\Component\Presenter\ApplicationService;

/**
 * Services that generates the frontend-facing configuration for a Mapbender application
 * @todo: plug in caching
 *
 * Instance registerd in container as mapbender.presenter.frontend.application.config.service
 */
class ConfigService extends ContainerAware
{
    /** @var ApplicationService */
    protected $basePresenter;

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
        $this->basePresenter = $container->get('mapbender.presenter.frontend.application.service');
    }

    /**
     * @return UrlGeneratorInterface
     */
    protected function getRouter()
    {
        /** @var UrlGeneratorInterface $router */
        $router = $this->container->get('router');
        return $router;
    }

    /**
     * @param Application $entity
     * @return mixed[]
     * @todo: absorb element extraction + grants check duties
     */
    public function getConfiguration(Application $entity)
    {
        $activeElements = $this->getActiveElements($entity);
        $configuration = array(
            'application' => $this->getBaseConfiguration($entity),
            'elements'    => $this->getElementConfiguration($activeElements),
        );
        $configuration += $this->getLayerSetConfiguration($entity);

        // Let (active, visible) Elements update the Application config
        // This is useful for BaseSourceSwitcher, SuggestMap, potentially many more, that influence the initially
        // visible state of the frontend.
        $configBeforeElement = $configAfterElements = $configuration;
        foreach ($activeElements as $elementComponent) {
            $configAfterElements = $configBeforeElement = $elementComponent->updateAppConfig($configBeforeElement);
        }
        return $configAfterElements;
    }

    public function getBaseConfiguration(Application $entity)
    {
        return array(
            'title'         => $entity->getTitle(),
            'urls'          => $this->getUrls($entity),
            'publicOptions' => $entity->getPublicOptions(),
            'slug'          => $entity->getSlug(),
        );
    }

    /**
     * Get runtime URLs
     * Hack to get proper urls when embedded in drupal
     * @todo: Drupal hacks should reside in DrupalIntegrationBundle
     *
     * @param Application $entity
     * @return array
     */
    public function getUrls(Application $entity)
    {
        $config        = array('slug' => $entity->getSlug());
        $router        = $this->getRouter();
        $searchSubject = 'mapbender';
        $drupal_mark   = function_exists('mapbender_menu') ? '?q=mapbender' : $searchSubject;

        $urls = array(
            'base'     => $this->container->get('request')->getBaseUrl(),
            'asset'    => $this->container->get('templating.helper.assets')->getUrl(null),
            'element'  => $router->generate('mapbender_core_application_element', $config),
            'trans'    => $router->generate('mapbender_core_translation_trans'),
            'proxy'    => $router->generate('owsproxy3_core_owsproxy_entrypoint'),
            'metadata' => $router->generate('mapbender_core_application_metadata', $config),
            'config'   => $router->generate('mapbender_core_application_configuration', $config));

        if ($searchSubject !== $drupal_mark) {
            foreach ($urls as $k => $v) {
                if ($k == "asset") {
                    continue;
                }
                $urls[$k] = str_replace($searchSubject, $drupal_mark, $v);
            }
        }

        return $urls;
    }

    /**
     * Returns layerset config. This is actually already an array with two subarrays 'layersets' (actual config)
     * and 'layersetmap' (layer titles).
     *
     * @param Application $entity
     * @return array[]
     */
    public function getLayerSetConfiguration(Application $entity)
    {
        $configs = array();
        $titles = array();
        foreach ($entity->getLayersets() as $layerSet) {
            $layerId       = '' . $layerSet->getId();
            $layerSetTitle = $layerSet->getTitle() ? $layerSet->getTitle() : $layerId;
            $layerSets     = array();

            foreach ($this->filterActiveSourceInstances($layerSet) as $layer) {
                /**
                 * @todo: pluggable config generator service per source type please
                 *        currently, we only have WMS...
                 */
                $instHandler = EntityHandler::createHandler($this->container, $layer);
                $conf        = $instHandler->getConfiguration($this->container->get('signer'));

                if (!$conf) {
                    continue;
                }

                $layerSets[] = array(
                    $layer->getId() => array(
                        'type'          => strtolower($layer->getType()),
                        'title'         => $layer->getTitle(),
                        'configuration' => $conf
                    )
                );
            }

            $configs[$layerId] = $layerSets;
            $titles[$layerId] = $layerSetTitle;
        }
        return array(
            'layersets' => $configs,
            'layersetmap' => $titles,
        );
    }

    /**
     * @param Element[] $elements Element Components
     * @return mixed[]
     */
    public static function getElementConfiguration($elements)
    {
        $elementConfig = array();
        foreach ($elements as $element) {
            $elementConfig[$element->getId()] = array(
                'init'          => $element->getWidgetName(),
                'configuration' => $element->getPublicConfiguration());
        }
        return $elementConfig;
    }

    /**
     * Extracts active source instances from given Layerset entity.
     *
     * @param Layerset $entity
     * @return SourceInstance[]
     */
    protected static function filterActiveSourceInstances(Layerset $entity)
    {
        $isYamlApp = $entity->getApplication()->isYamlBased();
        $activeInstances = array();
        foreach ($entity->getInstances() as $instance) {
            if ($isYamlApp || $instance->getEnabled()) {
                $activeInstances[] = $instance;
            }
        }
        return $activeInstances;
    }

    /**
     * Returns the list of Elements from the given Application that are enabled and granted for the current user
     * @param Application $entity
     * @return Element[]
     */
    protected function getActiveElements(Application $entity)
    {
        return $this->basePresenter->getActiveElements($entity);
    }
}
