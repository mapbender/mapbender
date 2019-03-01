<?php


namespace Mapbender\WmtsBundle\Component\Presenter;



use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Component\WmtsInstanceEntityHandler;
use Mapbender\WmtsBundle\Component\WmtsInstanceLayerEntityHandler;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;

class WmtsSourceService extends SourceService
{
    /**
     * @param WmtsInstance $sourceInstance
     * @return array|mixed[]|null
     */
    public function getInnerConfiguration(SourceInstance $sourceInstance)
    {
        $eh = new WmtsInstanceEntityHandler($this->container, $sourceInstance);
        $ehConfig = $eh->getConfiguration();
        $ownConfig = parent::getInnerConfiguration($sourceInstance) + array(
            'options' => $this->getOptionsConfiguration($sourceInstance),
            'children' => array($this->getRootLayerConfig($sourceInstance)),
        );
        return array_replace($ehConfig, $ownConfig);
    }

    /**
     * @param WmtsInstance $sourceInstance
     * @return array
     */
    public function getOptionsConfiguration($sourceInstance)
    {
        return array(
            "proxy" => $sourceInstance->getProxy(),
            "visible" => $sourceInstance->getVisible(),
            "opacity" => $sourceInstance->getOpacity() / 100,
        );
    }

    /**
     * @param WmtsInstance $sourceInstance
     * @return array
     */
    protected function getRootLayerConfig($sourceInstance)
    {
        // create a fake root layer entity
        $rootInst = new WmtsInstanceLayer();
        $rootInst->setTitle($sourceInstance->getRoottitle());
        $rootInst->setSourceItem(new WmtsLayerSource());
        $rootInst->setSourceInstance($sourceInstance);
        $rootInst->setActive($sourceInstance->getActive())
            ->setAllowinfo($sourceInstance->getAllowinfo())
            ->setInfo($sourceInstance->getInfo())
            ->setAllowtoggle($sourceInstance->getAllowtoggle())
            ->setToggle($sourceInstance->getToggle())
        ;
        $rootlayerHandler = new WmtsInstanceLayerEntityHandler($this->container, null);
        return $rootlayerHandler->generateConfiguration($rootInst);
    }
}
