<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Layer as Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base layer class for Mapbender3
 *
 * @author Christian Wygoda
 */
abstract class Layer {
    /**
     * @var ContainerInterface $container The container
     */
    protected $container;

    /**
     * @var Entity $entity
     */
    protected $entity;

    /**
     * @param ContainerInterface $container The container
     * @param Entity $entity The configuration entity
     */
    public function __construct(ContainerInterface $container,
        Entity $entity) {
        $this->container = $container;
        $this->entity = $entity;
   }

    /*************************************************************************
     *                                                                       *
     *                    Configuration entity handling                      *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the configuration entity.
     *
     * @return object $entity
     */
    public function getEntity() {
        return $this->entity;
    }

    /*************************************************************************
     *                                                                       *
     *             Shortcut functions for leaner Twig templates              *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the layer ID
     *
     * @return integer
     */
    public function getId() {
        return $this->entity->getId();
    }

    /**
     * Get the layer title
     *
     * @return string
     */
    public function getTitle() {
        return $this->entity->getTitle();
    }

    /*************************************************************************
     *                                                                       *
     *                              Frontend stuff                           *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the assets as an AsseticCollection.
     * Filters can be applied later on with the ensureFilter method.
     *
     * @param string $type Can be 'css' or 'js' to indicate which assets to dump
     * @return AsseticCollection
     */
    public function getAssets($type) {
        if($type !== 'css' && $type !== 'js') {
            throw new \RuntimeException('Asset type \'' . $type .
                '\' is unknown.');
        }

        return array();
    }

    public function getConfiguration() {
        return $this->entity->getConfiguration();
    }

    abstract public function getType();
 }

