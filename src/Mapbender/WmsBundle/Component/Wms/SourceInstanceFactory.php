<?php


namespace Mapbender\WmsBundle\Component\Wms;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\MinMax;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\RequestInformation;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;

class SourceInstanceFactory implements \Mapbender\Component\SourceInstanceFactory
{
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var string|null */
    protected $defaultLayerOrder;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string $defaultLayerOrder
     */
    public function __construct(EntityManagerInterface $entityManager, $defaultLayerOrder)
    {
        $this->entityManager = $entityManager;
        $this->defaultLayerOrder = $defaultLayerOrder;
    }

    /**
     * @param Source $source
     * @return WmsInstance
     */
    public function createInstance(Source $source)
    {
        /** @var WmsSource $source $instance */
        $instance = new WmsInstance();
        $instance->setSource($source);
        $instance->populateFromSource($source);

        if ($this->defaultLayerOrder) {
            $instance->setLayerOrder($this->defaultLayerOrder);
        }
        // avoid persistence errors (non-nullable column)
        $instance->setWeight(0);
        return $instance;
    }

    /**
     * @param array $data
     * @param string $id used for instance and as instance layer id prefix
     * @return WmsInstance
     */
    public function fromConfig(array $data, $id)
    {
        $source = $this->sourceFromConfig($data, $id);
        $instance = $this->createInstance($source);
        $instance->setId($id);
        $instance
            ->setTitle(ArrayUtil::getDefault($data, 'title', $source->getTitle()))
            ->setProxy(!isset($data['proxy']) ? false : $data['proxy'])
            ->setFormat(!isset($data['format']) ? 'image/png' : $data['format'])
            ->setInfoformat(!isset($data['info_format']) ? 'text/html' : $data['info_format'])
            ->setTransparency(!isset($data['transparent']) ? true : $data['transparent'])
            ->setOpacity(!isset($data['opacity']) ? 100 : $data['opacity'])
            ->setTiled(!isset($data['tiled']) ? false : $data['tiled'])
            ->setBasesource(!isset($data['isBaseSource']) ? true : $data['isBaseSource'])
        ;
        if (!empty($data['layerorder'])) {
            $instance->setLayerOrder($data['layerorder']);
        }
        $this->configureInstanceLayer($instance->getRootlayer(), $data);
        return $instance;
    }

    /**
     * @param SourceInstance $instance
     * @param Source[] $extraSources
     * @return Source|null
     */
    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources)
    {
        /** @var WmsInstance $instance */
        $repository = $this->entityManager->getRepository('MapbenderWmsBundle:WmsSource');
        $yamlSource = $instance->getSource();
        $matchValues = array(
            'originUrl' => $yamlSource->getOriginUrl(),
            'version' => $yamlSource->getVersion(),
            'type' => $yamlSource->getType(),
        );
        /** @var WmsSource[] $candidates */
        $candidates = $repository->findBy($matchValues);
        $extraSourcesCollection = new ArrayCollection($extraSources);
        $criteria = Criteria::create();
        foreach ($matchValues as $matchProperty => $matchValue) {
            $criteria->andWhere($criteria->expr()->eq($matchProperty, $matchValue));
        }
        $candidates = array_merge($candidates, $extraSourcesCollection->matching($criteria)->getValues());
        $requiredLayerIdents = array();
        foreach ($instance->getLayers() as $il) {
            if ($layerIdent = $this->getReusableLayerIdent($il->getSourceItem())) {
                $requiredLayerIdents[$layerIdent] = $il;
            }
        }
        if (!$requiredLayerIdents) {
            throw new \LogicException("Can't match WMS source instance with zero named layers");
        }
        foreach ($candidates as $index => $dbSourceCandidate) {
            $candidateLayerIdents = array();
            foreach ($dbSourceCandidate->getLayers() as $candidateLayer) {
                if ($layerIdent = $this->getReusableLayerIdent($candidateLayer)) {
                    $candidateLayerIdents[$layerIdent] = $candidateLayer;
                }
            }
            if (array_diff(array_keys($requiredLayerIdents), array_keys($candidateLayerIdents))) {
                unset($candidates[$index]);
            } else {
                // Filter props and layer structure match
                // Rewrite SourceItem references on instance layers
                foreach ($requiredLayerIdents as $layerIdent => $targetInstanceLayer) {
                    /** @var WmsInstanceLayer $targetInstanceLayer */
                    /** @var WmsLayerSource $sourceItem */
                    $sourceItem = $candidateLayerIdents[$layerIdent];
                    $targetInstanceLayer->setSourceItem($sourceItem);
                    $ilParent = $targetInstanceLayer->getParent();
                    $siParent = $sourceItem->getParent();
                    do {
                        if ($ilParent && !$siParent) {
                            throw new \LogicException("Parent layer depth mismatch for target layer {$targetInstanceLayer->getTitle()}");
                        }
                        $ilParent->setSourceItem($siParent);
                        $ilParent = $ilParent->getParent();
                        $siParent = $siParent->getParent();
                    } while ($ilParent);
                }
                $instance->setSource($dbSourceCandidate);
                return $dbSourceCandidate;
            }
        }
        return null;
    }

    /**
     * Bake layer name and nesting depth for named layers
     *
     * @param WmsLayerSource $layer
     * @return string|null
     */
    protected static function getReusableLayerIdent(WmsLayerSource $layer)
    {
        if ($name = $layer->getName()) {
            $depth = 0;
            $parent = $layer->getParent();
            while ($parent) {
                ++$depth;
                $parent = $parent->getParent();
            }
            return "{$name}:{$depth}";
        } else {
            return null;
        }
    }

    /**
     * @param WmsInstanceLayer $instanceLayer
     * @param array $data
     */
    protected function configureInstanceLayer(WmsInstanceLayer $instanceLayer, array $data)
    {
        $instanceLayer
            ->setId($instanceLayer->getSourceItem()->getId())
            ->setSelected(!isset($data["visible"]) ? true : $data["visible"])
            ->setInfo(!isset($data["queryable"]) ? false : $data["queryable"], true)
            ->setAllowinfo($instanceLayer->getInfo() !== null && $instanceLayer->getInfo())
            ->setToggle(ArrayUtil::getDefault($data, 'toggle', $instanceLayer->getParent() ? null : false))
            ->setAllowtoggle(ArrayUtil::getDefault($data, 'allowtoggle', $instanceLayer->getSourceItem()->getSublayer()->count() ? true : null))
        ;

        if (!empty($data['layers'])) {
            $instanceLayers = $instanceLayer->getSublayer()->getValues();
            foreach (array_values($data['layers']) as $childIndex => $childLayerData) {
                $this->configureInstanceLayer($instanceLayers[$childIndex], $childLayerData);
            }
        }
    }

    /**
     * @param array $data
     * @param string $id
     * @return WmsSource
     */
    protected function sourceFromConfig(array $data, $id)
    {
        $source = new WmsSource();
        $source
            ->setId($id)
            ->setTitle(ArrayUtil::getDefault($data, 'title', $id))
            ->setVersion(!isset($data['version']) ? '1.1.1' : $data['version'])
            ->setOriginUrl(!isset($data['url']) ? null : $data['url'])
        ;
        $getMap = new RequestInformation();
        $getMap->addFormat(!isset($data['format']) ? 'image/png' : $data['format']);
        // @todo: empty url is an error condition
        $getMap->setHttpGet(!isset($data['url']) ? null : $data['url']);
        $source->setGetMap($getMap);
        if (isset($data['info_format'])) {
            $getFeatureInfo = new RequestInformation();
            $getFeatureInfo->addFormat(!isset($data['info_format']) ? 'text/html' : $data['info_format']);
            // @todo: empty url is an error condition
            $getFeatureInfo->setHttpGet(!isset($data['url']) ? null : $data['url']);
            $source->setGetFeatureInfo($getFeatureInfo);
        }
        $this->rootLayerFromConfig($source, $data);
        return $source;
    }

    /**
     * @param WmsSource $source
     * @param array $data
     * @return WmsLayerSource
     */
    protected function rootLayerFromConfig(WmsSource $source, array $data)
    {
        return $this->layerFromConfig($source, $data, null);
    }

    /**
     * @param WmsSource $source
     * @param array $data
     * @param WmsLayerSource|null $parent
     * @param int $order
     * @return WmsLayerSource
     */
    protected function layerFromConfig(WmsSource $source, array $data, WmsLayerSource $parent = null, $order = 0)
    {
        $layer = new WmsLayerSource();
        $minScale = !isset($data["minScale"]) ? null : $data["minScale"];
        $maxScale = !isset($data["maxScale"]) ? null : $data["maxScale"];
        $layer
            ->setPriority($order)
            ->setSource($source)
            ->setScale(new MinMax($minScale, $maxScale))
            ->setTitle(!isset($data['title']) ? '' : $data['title'])
        ;
        if (!empty($data['name'])) {
            $layer->setName($data['name']);
        }
        if (!empty($data["legendurl"])) {
            $style = new Style();
            $style->setName(null);
            $style->setTitle(null);
            $style->setAbstract(null);
            $onlineResource = new OnlineResource();
            $onlineResource->setFormat(null);
            $onlineResource->setHref($data["legendurl"]);
            $legendUrl = new LegendUrl($onlineResource);
            $style->setLegendUrl($legendUrl);
            $layer->addStyle($style);
        }

        if ($parent) {
            $layer->setId($parent->getId() . '_' . $order);
            $parent->addSublayer($layer);
        } else {
            $layer->setId($source->getId() . '_' . $order);
        }
        $source->addLayer($layer);
        if (!empty($data['layers'])) {
            foreach (array_values($data['layers']) as $childOrder => $layerDef) {
                $this->layerFromConfig($source, $layerDef, $layer, $childOrder);
            }
        }
        return $layer;
    }

    public function getFormType(SourceInstance $instance)
    {
        return 'Mapbender\WmsBundle\Form\Type\WmsInstanceInstanceLayersType';
    }

    public function getFormTemplate(SourceInstance $instance)
    {
        return '@MapbenderWms/Repository/instance.html.twig';
    }
}
