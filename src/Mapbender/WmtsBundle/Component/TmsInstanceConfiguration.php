<?php
namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfiguration;
use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsSource;

/**
 *
 * @author Paul Schmidt
 */
class TmsInstanceConfiguration extends InstanceConfiguration
{
    public $layers;

    public function getLayers()
    {
        return $this->layers;
    }

    public function setLayers($layers)
    {
        $this->layers = $layers;
        return $this;
    }

    public function setOptions(InstanceConfigurationOptions $options)
    {
        throw new \LogicException("Nope");
    }

    public function getOptions()
    {
        throw new \LogicException("Nope");
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
     * @return array[] children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param array $child
     * @return $this
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
            "layers" => $this->layers,
        );
    }

    public function addLayers($container, WmtsInstance $entity)
    {
        /** @var WmtsSource $source */
        $source = $entity->getSource();
        $tilematrixsets = array();
        foreach ($source->getTilematrixsets() as $tilematrixset) {
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
        $instanceLayerHandler = new WmtsInstanceLayerEntityHandler($container, null);
        foreach ($entity->getLayers() as $layer) {
            if ($layer->getActive()) {
                $options = $instanceLayerHandler->generateConfiguration($layer);
                $format = $layer->getFormat();
                $options['options']['format'] = $format;
                $options['options']['tilematrixset'] = $tilematrixsets[$options['options']['identifier']];
                // TODO check if layers support info
                $layersConf[] = $options;
            }
        }
        $this->setLayers($layersConf);
    }

    /**
     * @inheritdoc
     */
    public static function fromArray($options, $strict = true)
    {
        throw new \Exception('not implemented yet.');
    }
}
