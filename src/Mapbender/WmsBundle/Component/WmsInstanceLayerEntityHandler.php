<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceItemEntityHandler;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Description of WmsInstanceLayerEntityHandler
 *
 * @author Paul Schmidt
 *
 * @property WmsInstanceLayer $entity
 */
class WmsInstanceLayerEntityHandler extends SourceInstanceItemEntityHandler
{

    /**
     * Creates a SourceInstanceItem
     *
     * @param WmsSource|SourceInstance  $instance
     * @param WmsLayerSource|SourceItem $layerSource
     * @param int                       $num
     * @return WmsInstanceLayer
     * @internal param  $item
     */
    public function create(SourceInstance $instance, SourceItem $layerSource, $num = 0)
    {
        /** @var WmsInstance $instance */
        $instanceLayer = $this->entity;

        $instanceLayer->setSourceInstance($instance);
        $instanceLayer->setSourceItem($layerSource);
        $instanceLayer->setTitle($layerSource->getTitle());

        $instanceLayer->setMinScale($layerSource->getMinScale());
        $instanceLayer->setMaxScale($layerSource->getMaxScale());

        $queryable = $layerSource->getQueryable();
        $instanceLayer->setInfo(Utils::getBool($queryable));
        $instanceLayer->setAllowinfo(Utils::getBool($queryable));
        $instanceLayer->setPriority($num);
        $instance->addLayer($instanceLayer);
        if ($layerSource->getSublayer()->count() > 0) {
            $instanceLayer->setToggle(false);
            $instanceLayer->setAllowtoggle(true);
        } else {
            $instanceLayer->setToggle(null);
            $instanceLayer->setAllowtoggle(null);
        }
        foreach ($layerSource->getSublayer() as $wmslayersourceSub) {
            $entityHandler = new WmsInstanceLayerEntityHandler($this->container, new WmsInstanceLayer());
            $entityHandler->create($instance, $wmslayersourceSub, $num + 1);
            $entityHandler->getEntity()->setParent($instanceLayer);
            $instanceLayer->addSublayer($entityHandler->getEntity());
        }
        return $instanceLayer;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        foreach ($this->entity->getSublayer() as $sublayer) {
            self::createHandler($this->container, $sublayer)->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getSublayer() as $sublayer) {
            self::createHandler($this->container, $sublayer)->remove();
        }
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    private function isScheduledForRemoval($entity)
    {
        $mgr = $this->container->get('doctrine')->getManager();
        $uow = $mgr->getUnitOfWork();
        $prop = new \ReflectionProperty(get_class($uow), 'entityDeletions');
        $prop->setAccessible(true);
        $list = $prop->getValue($uow);
        $cls = $mgr->getClassMetadata(get_class($entity))->getName();
        foreach ($list as $obj) {
            if ($mgr->getClassMetadata(get_class($obj))->getName() === $cls
                && $obj->getId() === $entity->getId()
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function update(SourceInstance $instance, SourceItem $wmslayersource)
    {
        /** @var WmsInstance $instance */
        /** @var WmsLayerSource $wmslayersource */
        $manager = $this->container->get('doctrine')->getManager();
        /* remove instance layers for missed layer sources */
        $toRemove = array();
        foreach ($this->entity->getSublayer() as $wmsinstlayer) {
            /** @var WmsInstanceLayer $wmsinstlayer */
            if ($this->isScheduledForRemoval($wmsinstlayer->getSourceItem())) {
                $toRemove[] = $wmsinstlayer;
            }
        }
        foreach ($toRemove as $rem) {
            $this->entity->getSublayer()->removeElement($rem);
            self::createHandler($this->container, $rem)->remove();
        }
        foreach ($wmslayersource->getSublayer() as $wmslayersourceSub) {
            $layer = $this->findLayer($wmslayersourceSub, $this->entity->getSublayer());
            if ($layer) {
                self::createHandler($this->container, $layer)->update($instance, $wmslayersourceSub);
            } else {
                $sublayerInstance = new WmsInstanceLayer();
                $sublayerHandler = new WmsInstanceLayerEntityHandler($this->container, $sublayerInstance);
                $obj = $sublayerHandler->create(
                    $instance,
                    $wmslayersourceSub,
                    $wmslayersourceSub->getPriority()
                );
                $obj->setParent($this->entity);
                $instance->getLayers()->add($obj);
                $this->entity->getSublayer()->add($obj);
                $manager->persist($obj);
                foreach ($obj->getSublayer() as $lay) {
                    $manager->persist($lay);
                }
            }
        }
        $this->entity->setPriority($wmslayersource->getPriority());
        $origMinMax = $wmslayersource->getScaleRecursive();
        $scaleMinMax = null;
        if ($origMinMax) {
            $minInrange = $origMinMax->getInRange($this->entity->getMinScale());
            $maxInrange = $origMinMax->getInRange($this->entity->getMaxScale());
            $scaleMinMax = new MinMax($minInrange, $maxInrange);
        } else {
            $scaleMinMax = new MinMax($this->entity->getMinScale(), $this->entity->getMaxScale());
        }
        $this->entity->setMinScale($scaleMinMax ? $scaleMinMax->getMin() : null);
        $this->entity->setMaxScale($scaleMinMax ? $scaleMinMax->getMax() : null);
        $queryable = Utils::getBool($wmslayersource->getQueryable(), true);
        if ($queryable === '0') {
            $queryable = false;
        }
        if ($queryable === '1') {
            $queryable = true;
        }
        $this->entity->setInfo($queryable === true ? $this->entity->getInfo() : $queryable);
        $this->entity->setAllowinfo($queryable === true ? $this->entity->getInfo() : $queryable);
        if ($wmslayersource->getSublayer()->count() > 0) {
            $this->entity->setToggle(is_bool($this->entity->getToggle()) ? $this->entity->getToggle() : false);
            $alowtoggle = is_bool($this->entity->getAllowtoggle()) ? $this->entity->getAllowtoggle() : true;
            $this->entity->setAllowtoggle($alowtoggle);
        } else {
            $this->entity->setToggle(null);
            $this->entity->setAllowtoggle(null);
        }
        $this->save();
    }

    /**
     * Generates a configuration for layers
     *
     * @return array
     */
    public function generateConfiguration()
    {
        $configuration = array();
        if ($this->entity->getActive() === true) {
            $children = null;
            if ($this->entity->getSublayer()->count() > 0) {
                $children = array();
                foreach ($this->entity->getSublayer() as $sublayer) {
                    $instLayHandler = self::createHandler($this->container, $sublayer);
                    $configurationTemp = $instLayHandler->generateConfiguration();
                    if (count($configurationTemp) > 0) {
                        $children[] = $configurationTemp;
                    }
                }
            }
            $layerConf = $this->getConfiguration();
            $configuration = array(
                "options" => $layerConf,
                "state" => array(
                    "visibility" => null,
                    "info" => null,
                    "outOfScale" => null,
                    "outOfBounds" => null
                ),
            );
            if ($children !== null) {
                $configuration["children"] = $children;
            }
        }
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = array(
            "id" => strval($this->entity->getId()),
            "priority" => $this->entity->getPriority(),
            "name" => $this->entity->getSourceItem()->getName() !== null ?
                $this->entity->getSourceItem()->getName() : "",
            "title" => $this->entity->getTitle(),
            "queryable" => $this->entity->getInfo(),
            "style" => $this->entity->getStyle(),
            "minScale" => $this->entity->getMinScale() !== null ? floatval($this->entity->getMinScale()) : null,
            "maxScale" => $this->entity->getMaxScale() !== null ? floatval($this->entity->getMaxScale()) : null
        );
        $srses = array();
        $llbbox = $this->entity->getSourceItem()->getLatlonBounds();
        if ($llbbox !== null) {
            $srses[$llbbox->getSrs()] = array(
                floatval($llbbox->getMinx()),
                floatval($llbbox->getMiny()),
                floatval($llbbox->getMaxx()),
                floatval($llbbox->getMaxy())
            );
        }
        foreach ($this->entity->getSourceItem()->getBoundingBoxes() as $bbox) {
            $srses[$bbox->getSrs()] = array(
                floatval($bbox->getMinx()),
                floatval($bbox->getMiny()),
                floatval($bbox->getMaxx()),
                floatval($bbox->getMaxy())
            );
        }
        $configuration['bbox'] = $srses;
        $styles = $this->entity->getSourceItem()->getStyles();
        if ($styles) {
            $legendurl = $styles[count($styles) - 1]->getLegendUrl(); // the last style from object's styles
            if ($legendurl !== null) {
                $configuration["legend"] = array(
                    "url" => $this->generateTunnelUrl($legendurl->getOnlineResource()->getHref()),
                    "width" => intval($legendurl->getWidth()),
                    "height" => intval($legendurl->getHeight())
                );
            }
        } elseif ($this->entity->getSourceInstance()->getSource()->getGetLegendGraphic() !== null &&
            $this->entity->getSourceItem()->getName() !== null &&
            $this->entity->getSourceItem()->getName() !== ""
        ) {
            $source = $this->entity->getSourceInstance()->getSource();
            $legend = $source->getGetLegendGraphic();
            $url = $legend->getHttpGet();
            $formats = $legend->getFormats();
            $params = "service=WMS&request=GetLegendGraphic"
                . "&version=" . $source->getVersion()
                . "&layer=" . $this->entity->getSourceItem()->getName()
                . (count($formats) > 0 ? "&format=" . $formats[0] : "")
                . "&sld_version=1.1.0";
            $legendgraphic = Utils::getHttpUrl($url, $params);
            $configuration["legend"] = array(
                "graphic" => $this->generateTunnelUrl($legendgraphic)
            );
        }
        $configuration["treeOptions"] = array(
            "info" => $this->entity->getInfo(),
            "selected" => $this->entity->getSelected(),
            "toggle" => $this->entity->getSublayer()->count() > 0 ? $this->entity->getToggle() : null,
            "allow" => array(
                "info" => $this->entity->getAllowinfo(),
                "selected" => $this->entity->getAllowselected(),
                "toggle" => $this->entity->getSublayer()->count() > 0 ? $this->entity->getAllowtoggle() : null,
                "reorder" => $this->entity->getAllowreorder(),
            )
        );
        return $configuration;
    }

    /**
     * Finds an instance layer, that is linked with a given wms source layer.
     *
     * @param WmsLayerSource $wmssourcelayer wms layer source
     * @param array $instancelayerList list of instance layers
     * @return WmsInstanceLayer | null the instance layer, otherwise null
     */
    public function findLayer(WmsLayerSource $wmssourcelayer, $instancelayerList)
    {
        foreach ($instancelayerList as $instancelayer) {
            if ($wmssourcelayer->getId() === $instancelayer->getSourceItem()->getId()) {
                return $instancelayer;
            }
        }
        return null;
    }

    private function generateTunnelUrl($url)
    {
        if ($this->entity->getSourceInstance()->getSource()->getUsername()) {
            $tunnelBaseUrl = $this->container->get('router')->generate(
                'mapbender_core_application_instancetunnel',
                array(
                    'slug' => $this->entity->getSourceInstance()->getLayerset()->getApplication()->getSlug(),
                    'instanceId' => $this->entity->getSourceInstance()->getId()),
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            // forward "request" param to tunnel (lower-case matching)
            $params = array();
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            foreach ($params as $name => $value) {
                if (strtolower($name) == 'request') {
                    $fullQueryString = strstr($url, '?', false);
                    return $tunnelBaseUrl . $fullQueryString;
                }
            }
            throw new \RuntimeException('Failed to tunnelify url, no `request` param found: ' . var_export($url, true));
        } else {
            return $url;
        }
    }
}
