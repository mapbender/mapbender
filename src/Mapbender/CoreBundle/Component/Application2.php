<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Application;

/**
 * This is the new application class, which is always build upon an entity
 * object.
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class Application2 {
    protected $container;
    protected $entity;
    /**
     * id
     * title
     * slug
     * description
     * template
     * elements
     * -owner
     * -layersets
     */
    protected $element_id_template;

    // Save some memory by keeping objects once instantiated
    private $template;

    /**
     * Standard constructor which will try to find the application by it's
     * entity id in the database
     */
    public function __construct($id, $container) {
        $this->container = $container;

        if($id) {
            $this->entity = $this->get('doctrine')
                ->getRepository('MapbenderCoreBundle:Application')
                ->findOneById($id);

            if(!$this->entity) {
                throw new \RuntimeException('The application with id "'
                    . $id .'" does not exist.');
            }
        } else {
            $this->entity = new Application();
        }

        $this->element_id_template = "element-%d";
    }

    /**
     * Short to the container get method
     */
    public function get($what) {
        return $this->container->get($what);
    }

    /**
     * Get the underlying entity object
     */
    public function getEntity() {
        return $this->entity;
    }

    /**
     * Get the template object
     */
    public function getTemplate() {
        if($this->template) {
            return $this->template;
        }

        $class = $this->getEntity()->getTemplate();
        $this->template = new $class($this->get('templating'));
        return $this->template;
    }

    /**
     * Get elements ordered by region
     */
    public function getElements() {
        $regions = array();
        $metadata = $this->getTemplate()->getMetadata();
        foreach($metadata['regions'] as $idx => $region) {
            $regions[$region] = array();
        }

        foreach($this->getEntity()->getElements() as $element) {
            $regions[$element->getRegion()][] = $element;
        }

        // Sort each region by weight
        foreach($regions as $region => $elements) {
            usort($elements, function($a, $b) {
                return $a->getWeight() - $b->getWeight();
            });
        }

        return $regions;
    }
}

