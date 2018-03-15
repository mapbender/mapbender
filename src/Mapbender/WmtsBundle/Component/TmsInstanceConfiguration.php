<?php
namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\InstanceConfiguration;
use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;
use Mapbender\WmtsBundle\Entity\WmtsInstance;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WmtsInstanceConfiguration
 *
 * @author Paul Schmidt
 */
class TmsInstanceConfiguration extends InstanceConfiguration
{

    /**
     * ORM\Column(type="array", nullable=true)
     */

    public $layers;

//    /**
//     * ORM\Column(type="array", nullable=true)
//     */
//    public $tilematrixsets;

    public function getLayers()
    {
        return $this->layers;
    }
//
//    public function getTilematrixsets()
//    {
//        return $this->tilematrixsets;
//    }

    public function setLayers($layers)
    {
        $this->layers = $layers;
        return $this;
    }
//
//    public function setTilematrixsets($tilematrixsets)
//    {
//        $this->tilematrixsets = $tilematrixsets;
//        return $this;
//    }
//
//
//
//    public function addTilematrixset($tilematrixset)
//    {
//        $this->tilematrixsets[] = $tilematrixset;
//        return $this;
//    }

    /**
     * Sets options
     * @param ServiceConfigurationOptions $options ServiceConfigurationOptions
     * @return InstanceConfiguration
     */
    public function setOptions(InstanceConfigurationOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Returns options
     * @return ServiceConfigurationOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets a children
     * @param array $children children
     * @return InstanceConfiguration
     */
    public function setChildren($children)
    {
        $this->children = $children;
        return $this;
    }

    /**
     * Returns a title
     * @return integer children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Returns a title
     * @return integer children
     */
    public function addChild($child)
    {
        $this->children[] = $child;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
            "type" => $this->type,
            "title" => $this->title,
            "isBaseSource" => $this->isBaseSource,
            "options" => $this->options->toArray(),
            "children" => $this->children,
            "layers" => $this->layers,
//            "tilematrixsets" => $this->tilematrixsets
        );
    }

    public function addLayers($container, WmtsInstance $entity, $rootnode)
    {
        $tilematrixsets = array();
        foreach ($entity->getSource()->getTilematrixsets() as $tilematrixset) {
            $tilematrices = $tilematrixset->getTilematrices();
            $origin = $tilematrices[0]->getTopleftcorner();
            $tilewidth = $tilematrices[0]->getTilewidth();
            $tileheight = $tilematrices[0]->getTileheight();
            $tilematricesArr = array();
            $multiTopLeft = false;
            $multiTileSize = false;
            foreach ($tilematrices as $tilematrix) {
                $latlon = $tilematrix->getTopleftcorner();
                if ($origin[0] !== $latlon[0] || $origin[1] !== $latlon[1]) {
                    $multiTopLeft = true;
                }
                if ($tilewidth !== $tilematrix->getTilewidth() || $tileheight !== $tilematrix->getTileheight()) {
                    $multiTileSize = true;
                }
                $tilematricesArr[] = array(
                    'href' => $tilematrix->getHref(),
                    'order' => $tilematrix->getIdentifier(),
                    'units-per-pixel' => $tilematrix->getScaledenominator(),
                );
            }
            $tilematrixsets[$tilematrixset->getIdentifier()] = array(
                'id' => $tilematrixset->getId(),
                'tileSize' => array($tilewidth, $tileheight),
                'identifier' => $tilematrixset->getIdentifier(),
                'supportedCrs' => $tilematrixset->getSupportedCrs(),
                'origin' => $origin,
                'tilesets' => $tilematricesArr,
            );
        }
        $layersConf = array();
        foreach ($entity->getLayers() as $layer) {
            if ($layer->getActive()) {
                $options = EntityHandler::createHandler($container, $layer)->generateConfiguration();
                $format = $layer->getFormat();
                $options['options']['format'] = $format;
                $options['options']['format_ext'] =
                    strpos($format, '/') ? substr($format, strpos($format, '/') + 1) : null;
                $options['options']['tilematrixset'] = $tilematrixsets[$options['options']['identifier']];
                // TODO check if layers support info
                $layersConf[] = $options;
            }
        }
        $this->setLayers($layersConf);
        $this->addChild($rootnode);
//        foreach ($entity->getSource()->getTilematrixsets() as $tilematrixset) {
//            $tilematrices = $tilematrixset->getTilematrices();
//            $origin = $tilematrices[0]->getTopleftcorner();
//            $tilewidth = $tilematrices[0]->getTilewidth();
//            $tileheight = $tilematrices[0]->getTileheight();
//            $tilematricesArr = array();
//            $multiTopLeft = false;
//            $multiTileSize = false;
//            foreach ($tilematrices as $tilematrix) {
//                $latlon = $tilematrix->getTopleftcorner();
//                if ($origin[0] !== $latlon[0] || $origin[1] !== $latlon[1]) {
//                    $multiTopLeft = true;
//                }
//                if ($tilewidth !== $tilematrix->getTilewidth() || $tileheight !== $tilematrix->getTileheight()) {
//                    $multiTileSize = true;
//                }
//                $tilematricesArr[] = array(
//                    'href' => $tilematrix->getHref(),
//                    'order' => $tilematrix->getIdentifier(),
//                    'units-per-pixel' => $tilematrix->getScaledenominator(),
//                );
//            }
//            $this->addTilematrixset(array(
//                'id' => $tilematrixset->getId(),
//                'tileSize' => array($tilewidth, $tileheight),
//                'identifier' => $tilematrixset->getIdentifier(),
//                'supportedCrs' => $tilematrixset->getSupportedCrs(),
//                'origin' => $origin,
//                'tilesets' => $tilematricesArr,
//            ));
//        }
    }

    /**
     * @inheritdoc
     */
    public static function fromArray($options)
    {
        throw new \Exception('not implemented yet.');
        $ic = null;
        if ($options && is_array($options)) {
            $ic = new WmtsInstanceConfiguration();
            if (isset($options['type'])) {
                $ic->type = $options['type'];
            }
            if (isset($options['title'])) {
                $ic->title = $options['title'];
            }
            if (isset($options['isBaseSource'])) {
                $ic->isBaseSource = $options['isBaseSource'];
            }
            if (isset($options['options'])) {
                $ic->options = WmtsInstanceConfigurationOptions::fromArray($options['options']);
            }
        }
        return $ic;
    }
}
