<?php
namespace Mapbender\WmtsBundle\Component;

use Mapbender\WmtsBundle\Entity\WmtsInstance;

/**
 *
 * @author Paul Schmidt
 */
class WmtsInstanceConfiguration extends TmsInstanceConfiguration
{
    public $tilematrixsets;

    public function toArray()
    {
        return parent::toArray() + array(
            "tilematrixsets" => $this->tilematrixsets,
        );
    }

    public function getTilematrixsets()
    {
        return $this->tilematrixsets;
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

    public function addLayers($container, WmtsInstance $entity)
    {
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
    }
}
