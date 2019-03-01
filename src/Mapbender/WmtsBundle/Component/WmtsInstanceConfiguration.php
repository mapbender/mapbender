<?php
namespace Mapbender\WmtsBundle\Component;

Use Mapbender\CoreBundle\Component\EntityHandler;
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
class WmtsInstanceConfiguration extends InstanceConfiguration
{

    /**
     * ORM\Column(type="array", nullable=true)
     */

    public $layers;

    /**
     * ORM\Column(type="array", nullable=true)
     */
    public $tilematrixsets;

    public function getLayers()
    {
        return $this->layers;
    }

    public function getTilematrixsets()
    {
        return $this->tilematrixsets;
    }

    public function setLayers($layers)
    {
        $this->layers = $layers;
        return $this;
    }

    public function setTilematrixsets($tilematrixsets)
    {
        $this->tilematrixsets = $tilematrixsets;
        return $this;
    }



    public function addTilematrixset(array $tilematrixset)
    {
        $this->tilematrixsets[] = $tilematrixset;
        return $this;
    }

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
            "tilematrixsets" => $this->tilematrixsets
        );
    }

    public function addLayers($container, WmtsInstance $entity, $rootnode)
    {
        $layersConf = array();
        foreach ($entity->getLayers() as $layer) {
            if ($layer->getActive()) {
                $options = EntityHandler::createHandler($container, $layer)->generateConfiguration();
                // TODO check if layers support info
                $layersConf[] = $options;
            }
        }
        $this->setLayers($layersConf);
        $this->addChild($rootnode);
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
                    'identifier' => $tilematrix->getIdentifier(),
                    'scaleDenominator' => $tilematrix->getScaledenominator(),
                    'tileWidth' => $tilematrix->getTilewidth(),
                    'tileHeight' => $tilematrix->getTileheight(),
                    'topLeftCorner' => $latlon,
                    'matrixSize' =>  array($tilematrix->getMatrixwidth(), $tilematrix->getMatrixheight())
                );
            }
            // clean matrix attributes if matrices have a selfsame value
            if (!$multiTopLeft || !$multiTileSize) {
                foreach ($tilematricesArr as &$tmatrix) {
                    if (!$multiTopLeft) {
                        unset($tmatrix['topLeftCorner']);
                    }
                    if (!$multiTileSize) {
                        unset($tmatrix['tileWidth']);
                        unset($tmatrix['tileHeight']);
                    }
                }
            }
            $this->addTilematrixset(array(
                'id' => $tilematrixset->getId(),
                'tileSize' => array($tilewidth, $tileheight),
                'identifier' => $tilematrixset->getIdentifier(),
                'supportedCrs' => $tilematrixset->getSupportedCrs(),
                'origin' => $origin,
                'tilematrices' => $tilematricesArr
            ));
        }
        $a = 0;
    }

    /**
     * @inheritdoc
     */
    public static function fromArray($options, $stict = true)
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
