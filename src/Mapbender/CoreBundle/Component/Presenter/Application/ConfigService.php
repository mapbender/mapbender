<?php

namespace Mapbender\CoreBundle\Component\Presenter\Application;

use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Services that generates the frontend-facing configuration for a Mapbender application
 * @todo: plug in caching
 *
 * Instance registerd in container as mapbender.presenter.frontend.application.config.service
 */
class ConfigService extends ContainerAware
{
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
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
        foreach ($this->getLayersetObjectMap($entity) as $layerSet) {
            $layerId       = '' . $layerSet->getId();
            $layerSetTitle = $layerSet->getTitle() ? $layerSet->getTitle() : $layerId;
            $layerSets     = array();

            foreach ($layerSet->layerObjects as $layer) {
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
     * Extracts active layersets and instances from given Application entity.
     *
     * Side-effect: source instances are actually smeared into the Layerset entity, in the (otherwise unused) `layerObjects`
     * attribute.
     * @todo: remove entity-modifying side effect
     *
     * @param Application $entity
     * @return Layerset[]
     */
    protected function getLayersetObjectMap(Application $entity)
    {
        $layersetMap = array();
        foreach ($entity->getLayersets() as $layerSet) {
            $layerSet->layerObjects = array();
            foreach ($layerSet->getInstances() as $instance) {
                if ($entity->isYamlBased() || $instance->getEnabled()) {
                    $layerSet->layerObjects[] = $instance;
                }
            }
            $layersetMap[$layerSet->getId()] = $layerSet;
        }
        return $layersetMap;
    }
}
