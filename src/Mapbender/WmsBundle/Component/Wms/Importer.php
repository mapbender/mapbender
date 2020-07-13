<?php

namespace Mapbender\WmsBundle\Component\Wms;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Mapbender\Component\Loader\RefreshableSourceLoader;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Component\XmlValidatorService;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service class that produces WmsSource entities by evaluating a "GetCapabilities" document, either directly
 * in-memory, or from a given HttpOriginInterface (which contains url + username + password).
 * WmsSource is wrapped in a Response class for legacy reasons (previously bundled with deferred-evaluation validation
 * constructs).
 *
 * An instance is registered in container as mapbender.importer.source.wms.service, see services.xml
 *
 * @method WmsSource evaluateServer(HttpOriginInterface $origin)
 */
class Importer extends RefreshableSourceLoader
{
    /** @var XmlValidatorService */
    protected $validator;
    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param HttpTransportInterface $transport
     * @param EntityManager $entityManager
     * @param XmlValidatorService $validator;
     */
    public function __construct(HttpTransportInterface $transport,
                                EntityManager $entityManager,
                                XmlValidatorService $validator)
    {
        parent::__construct($transport);
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     * @throws InvalidUrlException
     */
    protected function getResponse(HttpOriginInterface $origin)
    {
        static::validateUrl($origin->getOriginUrl());
        return $this->capabilitiesRequest($origin);
    }

    public function parseResponseContent($content)
    {
        $document = WmsCapabilitiesParser::createDocument($content);
        $parser = WmsCapabilitiesParser::getParser($document);
        $source = $parser->parse($document);
        $this->assignLayerPriorities($source->getRootlayer(), 0);
        return $source;
    }

    public function validateResponseContent($content)
    {
        $document = WmsCapabilitiesParser::createDocument($content);
        $this->validator->validateDocument($document);
    }

    /**
     * @param Source $target
     * @param Source $reloaded
     * @throws \Exception
     */
    public function updateSource(Source $target, Source $reloaded)
    {
        $this->beforeSourceUpdate($target, $reloaded);
        /** @var WmsSource $target */
        /** @var WmsSource $reloaded */
        $classMeta = $this->entityManager->getClassMetadata(ClassUtils::getClass($target));
        EntityUtil::copyEntityFields($target, $reloaded, $classMeta, false);

        $contact = clone $reloaded->getContact();
        $this->entityManager->detach($contact);
        if ($target->getContact()) {
            $this->entityManager->remove($target->getContact());
        }
        $target->setContact($contact);
        $this->entityManager->remove($reloaded->getContact());

        $this->updateLayer($target->getRootlayer(), $reloaded->getRootlayer());
        $this->assignLayerPriorities($target->getRootlayer(), 0);

        $this->copyKeywords($target, $reloaded, 'Mapbender\WmsBundle\Entity\WmsSourceKeyword');
        /** @var ApplicationRepository $applicationRepository */
        $applicationRepository = $this->entityManager->getRepository('\Mapbender\CoreBundle\Entity\Application');
        foreach ($applicationRepository->findWithInstancesOf($target) as $application) {
            $application->setUpdated(new \DateTime('now'));
            $this->entityManager->persist($application);
        }

        foreach ($target->getInstances() as $instance) {
            $this->updateInstance($instance);
            $this->entityManager->persist($instance);
        }
    }

    /**
     * @param Source $target
     * @return string
     */
    public function getRefreshUrl(Source $target)
    {
        /** @var WmsSource $target */
        $persistedUrl = $target->getOriginUrl();
        $detectedVersion = UrlUtil::getQueryParameterCaseInsensitive($persistedUrl, 'version', null);
        if ($detectedVersion) {
            return $persistedUrl;
        } else {
            return  UrlUtil::validateUrl($persistedUrl, array(
                'VERSION' => $target->getVersion(),
            ));
        }
    }

    /**
     * @param HttpOriginInterface $serviceOrigin
     * @return Response
     */
    protected function capabilitiesRequest(HttpOriginInterface $serviceOrigin)
    {
        $addParams = array();
        $url = $serviceOrigin->getOriginUrl();
        $addParams['REQUEST'] = 'GetCapabilities';
        if (!UrlUtil::getQueryParameterCaseInsensitive($url, 'service')) {
            $addParams['SERVICE'] = 'WMS';
        }
        $url = UrlUtil::validateUrl($url, $addParams);
        $url = UrlUtil::addCredentials($url, $serviceOrigin->getUsername(), $serviceOrigin->getPassword(), false);
        return $this->httpTransport->getUrl($url);
    }

    /**
     * @param WmsLayerSource $target
     * @param WmsLayerSource $updatedLayer
     */
    private function updateLayer(WmsLayerSource $target, WmsLayerSource $updatedLayer)
    {
        $classMeta = $this->entityManager->getClassMetadata(ClassUtils::getClass($target));
        EntityUtil::copyEntityFields($target, $updatedLayer, $classMeta, false);
        $this->copyKeywords($target, $updatedLayer, 'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword');

        /* handle sublayer- layer. Name is a unique identifier for a wms layer. */
        /* remove missed layers */
        $updatedSubLayers = $updatedLayer->getSublayer();
        $targetSubLayers = $target->getSublayer();
        foreach ($targetSubLayers as $layerOldSub) {
            $removeChild = !$this->findLayer($layerOldSub, $updatedSubLayers);
            if ($removeChild) {
                $this->entityManager->remove($layerOldSub);
                // NOTE: child layer is reachable from TWO different association collections and must be
                //       manually removed from both, or it will be rediscovered and re-saved on flush
                $targetSubLayers->removeElement($layerOldSub);
                $target->getSource()->getLayers()->removeElement($layerOldSub);
            }
        }
        /* update founded layers, add new layers */
        foreach ($updatedSubLayers as $subItemNew) {
            $subItemsOld = $this->findLayer($subItemNew, $targetSubLayers);
            if (count($subItemsOld) === 1) {
                // update single layer
                $this->updateLayer($subItemsOld[0], $subItemNew);
            } else {
                foreach ($subItemsOld as $layerToRemove) {
                    $this->entityManager->remove($layerToRemove);
                    $targetSubLayers->removeElement($layerToRemove);
                    $target->getSource()->getLayers()->removeElement($layerToRemove);
                }
                $this->setLayerSourceRecursive($subItemNew, $target->getSource());
                $target->addSublayer($subItemNew);
            }
        }
    }

    /**
     * Finds a layers at the layerlist.
     * @param WmsLayerSource $layer
     * @param WmsLayerSource[] $layerList
     * @return WmsLayerSource[]
     */
    private function findLayer($layer, $layerList)
    {
        $found = array();
        $matchName = $layer->getName();
        $matchTitle = $layer->getTitle();

        foreach ($layerList as $candidate) {
            $namesMatch = $matchName && $matchName === $candidate->getName();
            $titlesMatch = $matchTitle && $matchTitle === $candidate->getTitle();
            if ($namesMatch || (!$matchName && $titlesMatch)) {
                $found[] = $candidate;
            }
        }
        return $found;
    }

    /**
     * @param WmsLayerSource $layer
     * @param WmsSource $source
     */
    private function setLayerSourceRecursive(WmsLayerSource $layer, WmsSource $source)
    {
        $layer->setSource($source);
        $source->getLayers()->add($layer);
        foreach ($layer->getSublayer() as $child) {
            $this->setLayerSourceRecursive($child, $source);
        }
    }

    private function updateInstance(WmsInstance $instance)
    {
        $source = $instance->getSource();
        $this->pruneInstanceLayers($instance);

        if ($getMapFormats = $source->getGetMap()->getFormats()) {
            if (!in_array($instance->getFormat(), $getMapFormats)) {
                $instance->setFormat($getMapFormats[0]);
            }
        } else {
            $instance->setFormat(null);
        }
        if ($source->getGetFeatureInfo() && $featureInfoFormats = $source->getGetFeatureInfo()->getFormats()) {
            if (!in_array($instance->getInfoformat(), $featureInfoFormats)) {
                $instance->setInfoformat($featureInfoFormats[0]);
            }
        } else {
            $instance->setInfoformat(null);
        }
        if ($exceptionFormats = $source->getExceptionFormats()) {
            if (!in_array($instance->getExceptionformat(), $exceptionFormats)) {
                $instance->setExceptionformat($exceptionFormats[0]);
            }
        } else {
            $instance->setExceptionformat(null);
        }
        $this->updateInstanceDimensions($instance);
        $instanceRoot = $instance->getRootlayer();
        if (!$instanceRoot) {
            $instanceRoot = new WmsInstanceLayer();
            $instanceRoot->populateFromSource($instance, $instance->getSource()->getRootlayer());
            $instance->setLayers(new ArrayCollection(array($instanceRoot)));
        } else {
            $this->updateInstanceLayer($instanceRoot);
        }
        $this->assignLayerPriorities($instanceRoot, 0);
    }

    /**
     * @param WmsInstanceLayer $target
     * @param ArrayCollection|WmsLayerSource[] $sourceChildren
     */
    protected function updateInstanceLayerChildren(WmsInstanceLayer $target, $sourceChildren)
    {
        // reorder source layers already configured in instance to respect previous subset layer ordering
        $commonChildSources = new ArrayCollection();
        $commonChildInstances = new ArrayCollection();
        foreach ($target->getSublayer() as $instanceChild) {
            $this->updateInstanceLayer($instanceChild);
            $commonChildSources->add($instanceChild->getSourceItem());
            $commonChildInstances->add($instanceChild);
        }
        $target->setSublayer(new ArrayCollection());
        $nextReorderedSource = 0;
        foreach ($sourceChildren as $sourceChild) {
            $instanceChildIndex = $commonChildSources->indexOf($sourceChild);
            if ($instanceChildIndex !== false) {
                $target->addSublayer($commonChildInstances[$nextReorderedSource]);
                ++$nextReorderedSource;
            } else {
                $instance = $target->getSourceInstance();
                $sublayerInstance = new WmsInstanceLayer();
                $sublayerInstance->populateFromSource($instance, $sourceChild);
                $instance->getLayers()->add($sublayerInstance);
                $target->addSublayer($sublayerInstance);
                $this->entityManager->persist($sublayerInstance);
            }
        }
    }

    private function updateInstanceLayer(WmsInstanceLayer $target)
    {
        $sourceItem = $target->getSourceItem();
        $this->updateInstanceLayerChildren($target, $sourceItem->getSublayer());
        $queryable = $sourceItem->getQueryable();
        if (!$queryable) {
            if ($queryable !== null) {
                $queryable = false;
            }
            $target->setInfo($queryable);
            $target->setAllowinfo($queryable);
        }
        if ($sourceItem->getSublayer()->count() > 0) {
            $target->setToggle(is_bool($target->getToggle()) ? $target->getToggle() : false);
            $alowtoggle = is_bool($target->getAllowtoggle()) ? $target->getAllowtoggle() : true;
            $target->setAllowtoggle($alowtoggle);
        } else {
            $target->setToggle(null);
            $target->setAllowtoggle(null);
        }
        $this->entityManager->persist($target);
    }

    /**
     * @param WmsInstance $instance
     */
    private function updateInstanceDimensions(WmsInstance $instance)
    {
        $dimensionsOld = $instance->getDimensions();
        $sourceDimensions = $instance->getSource()->dimensionInstancesFactory();
        $dimensions = array();
        foreach ($sourceDimensions as $sourceDimension) {
            $newDimension = null;
            foreach ($dimensionsOld as $oldDimension) {
                if ($sourceDimension->compare($oldDimension)) {
                    /* replace attribute values */
                    $oldDimension->setUnitSymbol($sourceDimension->getUnitSymbol());
                    $oldDimension->setNearestValue($sourceDimension->getNearestValue());
                    $oldDimension->setCurrent($sourceDimension->getCurrent());
                    $oldDimension->setMultipleValues($sourceDimension->getMultipleValues());
                    $newDimension = $oldDimension;
                    break;
                }
            }
            if (!$newDimension) {
                $newDimension = clone $sourceDimension;
            }
            $dimensions[] = $newDimension;
        }
        $instance->setDimensions($dimensions);
    }

    /**
     * @param ContainingKeyword $target
     * @param ContainingKeyword $source
     * @param string $keywordClass
     */
    private function copyKeywords(ContainingKeyword $target, ContainingKeyword $source, $keywordClass)
    {
        KeywordUpdater::updateKeywords($target, $source, $this->entityManager, $keywordClass);
    }

    /**
     * @param WmsLayerSource|WmsInstanceLayer $layer
     * @param integer $value
     * @return int|mixed
     */
    protected function assignLayerPriorities($layer, $value)
    {
        $layer->setPriority($value);
        $offset = 1;
        foreach ($layer->getSublayer()->getValues() as $child) {
            $offset += $this->assignLayerPriorities($child, $value + $offset);
        }
        return $offset;
    }

    /**
     * @param WmsInstance $instance
     * @deprecated update the doctrine schema, so the delete cascade handles this
     */
    protected function pruneInstanceLayers(WmsInstance $instance)
    {
        // This might be completely redundant on current schema. There is
        // a delete cascade on the instance layer => source item relation.
        // This remains only as a BC amenity for outdated schema
        $uow = $this->entityManager->getUnitOfWork();
        do {
            $deleted = false;
            foreach ($instance->getLayers() as $instanceLayer) {
                $sourceLayer = $instanceLayer->getSourceItem();
                if ($uow->isScheduledForDelete($sourceLayer)) {
                    $this->entityManager->remove($instanceLayer);
                    $deleted = $this->pruneInstanceSubLayer($instance->getRootlayer(), $instanceLayer);
                    if ($deleted) {
                        // restart loop after modifying collection(s)
                        break;
                    }
                }
            }
        } while ($deleted);
    }

    /**
     * @param WmsInstanceLayer $parent
     * @param WmsInstanceLayer $toRemove
     * @deprecated update the doctrine schema, so the delete cascade handles this
     * @return bool
     */
    protected function pruneInstanceSubLayer(WmsInstanceLayer $parent, WmsInstanceLayer $toRemove)
    {
        // depth first recursion
        foreach ($parent->getSublayer() as $sublayer) {
            if ($this->pruneInstanceSubLayer($sublayer, $toRemove)) {
                return true;
            }
            if ($sublayer === $toRemove) {
                $parent->getSublayer()->removeElement($toRemove);
                $toRemove->getSourceInstance()->getLayers()->removeElement($toRemove);
                return true;
            }
        }
        return false;
    }
}
