<?php

namespace Mapbender\WmsBundle\Component\Wms;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Mapbender\Component\Loader\RefreshableSourceLoader;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\XmlValidator;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\Wms\Importer\DeferredValidation;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service class that produces WmsSource entities by evaluating a "GetCapabilities" document, either directly
 * in-memory, or from a given WmsOrigin (which is just url + username + password).
 * WmsSource is bundled in a Response class with validation errors. This is done because validation exceptions
 * can be optionally suppressed ("onlyValid"=false). In that case, the Response will contain the exception, if
 * any. By default, validation exceptions are thrown.
 *
 * An instance is registered in container as mapbender.importer.source.wms.service, see services.xml
 */
class Importer extends RefreshableSourceLoader
{
    /** @var ContainerInterface */
    protected $container;
    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param HttpTransportInterface $transport
     * @param EntityManager $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(HttpTransportInterface $transport,
                                EntityManager $entityManager,
                                ContainerInterface $container)
    {
        parent::__construct($transport);
        $this->entityManager = $entityManager;
        $this->container = $container;
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

    protected function parseResponseContent($content)
    {
        $document = WmsCapabilitiesParser::createDocument($content);
        $parser = WmsCapabilitiesParser::getParser($document);
        return new Importer\Response($parser->parse($document), $document);
    }

    /**
     * Performs a GetCapabilities request against WMS at $serviceOrigin and returns a WmsSource instance and the
     * (suppressed) XML validation error, if any, wrapped in a ImporterResponse object.
     *
     * @param HttpOriginInterface $serviceOrigin
     * @param bool $onlyValid
     * @return \Mapbender\WmsBundle\Component\Wms\Importer\Response
     * @throws \Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException
     * @throws \Mapbender\WmsBundle\Component\Exception\WmsException
     */
    public function evaluateServer(HttpOriginInterface $serviceOrigin, $onlyValid=true)
    {
        /** @var Importer\Response $response */
        $response = parent::evaluateServer($serviceOrigin, $onlyValid);
        if ($onlyValid) {
            $validationError = new DeferredValidation($response->getSource(), $response->getDocument(), $this);
        } else {
            $validationError = null;
        }
        return new Importer\Response($response->getSource(), $response->getDocument(), $validationError);
    }

    /**
     * Checks / evaluates a capabilities document returns a WmsSource instance and the (suppressed) XML validation error,
     * if any, wrapped in an Importer\Response object.
     *
     * @param \DOMDocument $document
     * @param boolean $onlyValid
     * @return \Mapbender\WmsBundle\Component\Wms\Importer\Response
     * @throws XmlParseException
     * @throws \Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException
     */
    public function evaluateCapabilitiesDocument(\DOMDocument $document, $onlyValid=true)
    {
        $parser = WmsCapabilitiesParser::getParser($document);
        if ($onlyValid) {
            $this->validate($document);
            $sourceEntity = $parser->parse();
            $sourceEntity->setValid(true);
            $validationError = null;
        } else {
            $sourceEntity = $parser->parse();
            $validationError = new DeferredValidation($sourceEntity, $document, $this);
            // valid attribute on WmsSource will be updated by deferred validation
            $sourceEntity->setValid(true);
        }
        return new Importer\Response($sourceEntity, $document, $validationError);
    }

    /**
     * @param Source $target
     * @param Source $reloaded
     * @throws \Exception
     * @internal, public only for legacy EntityHandler access
     */
    public function updateSource(Source $target, Source $reloaded)
    {
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

        $this->updateLayer($target->getRootlayer(), $reloaded->getRootlayer());

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
     * @param \DOMDocument $capsDocument
     * @throws XmlParseException
     */
    public function validate(\DOMDocument $capsDocument)
    {
        $validator = new XmlValidator($this->container);
        $validator->validate($capsDocument);
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
        $priorityOriginal = $target->getPriority();
        $classMeta = $this->entityManager->getClassMetadata(ClassUtils::getClass($target));
        EntityUtil::copyEntityFields($target, $updatedLayer, $classMeta, false);
        // restore original priority
        $target->setPriority($priorityOriginal);
        $this->copyKeywords($target, $updatedLayer, 'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword');

        /* handle sublayer- layer. Name is a unique identifier for a wms layer. */
        /* remove missed layers */
        $updatedSubLayers = $updatedLayer->getSublayer();
        $targetSubLayers = $target->getSublayer();
        foreach ($targetSubLayers as $layerOldSub) {
            $layerSublayer = $this->findLayer($layerOldSub, $updatedSubLayers);
            if (count($layerSublayer) !== 1) {
                $this->entityManager->remove($layerOldSub);
            }
        }
        $num = 0;
        /* update founded layers, add new layers */
        foreach ($updatedSubLayers as $subItemNew) {
            $num++;
            $subItemsOld = $this->findLayer($subItemNew, $targetSubLayers);
            if (count($subItemsOld) === 1) {
                // update single layer
                $subItemsOld[0]->setPriority($priorityOriginal + $num);
                $this->updateLayer($subItemsOld[0], $subItemNew);
            } else {
                foreach ($subItemsOld as $layerToRemove) {
                    $this->entityManager->remove($layerToRemove);
                }
                $lay = $this->cloneLayer($subItemNew, $target);
                $lay->setPriority($priorityOriginal + $num);
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
     * @param WmsLayerSource $toClone
     * @param WmsLayerSource $cloneParent
     * @return WmsLayerSource
     */
    private function cloneLayer(WmsLayerSource $toClone, WmsLayerSource $cloneParent)
    {
        $cloned = clone $toClone;
        $this->entityManager->detach($cloned);
        $cloned->setId(null);
        $cloned->setSource($cloneParent->getSource());
        $cloned->setParent($cloneParent);
        $cloned->setPriority($cloneParent->getPriority());
        $cloned->setKeywords(new ArrayCollection());
        $cloneParent->addSublayer($cloned);
        $this->copyKeywords($cloned, $toClone, 'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword');
        $this->entityManager->persist($cloned);
        if ($cloned->getSublayer()->count() > 0) {
            $children = new ArrayCollection();
            foreach ($cloned->getSublayer() as $subToClone) {
                $subCloned = $this->cloneLayer($subToClone, $cloned);
                $children->add($subCloned);
            }
            $cloned->setSublayer($children);
        }
        return $cloned;
    }


    private function updateInstance(WmsInstance $instance)
    {
        $source = $instance->getSource();

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
        $this->updateInstanceLayer($instance->getRootlayer());
    }

    private function updateInstanceLayer(WmsInstanceLayer $target)
    {
        /* remove instance layers for missed layer sources */
        foreach ($target->getSublayer() as $wmsinstlayer) {
            if ($this->entityManager->getUnitOfWork()->isScheduledForDelete($wmsinstlayer->getSourceItem())) {
                $target->getSublayer()->removeElement($wmsinstlayer);
                $this->entityManager->remove($wmsinstlayer);
            }
        }
        $sourceItem = $target->getSourceItem();
        foreach ($sourceItem->getSublayer() as $wmslayersourceSub) {
            $layer = $this->findInstanceLayer($wmslayersourceSub, $target->getSublayer());
            if ($layer) {
                $this->updateInstanceLayer($layer);
            } else {
                $instance = $target->getSourceInstance();
                $sublayerInstance = new WmsInstanceLayer();
                $sublayerInstance->populateFromSource($instance, $wmslayersourceSub, $wmslayersourceSub->getPriority());
                $sublayerInstance->setParent($target);
                $instance->getLayers()->add($sublayerInstance);
                $target->getSublayer()->add($sublayerInstance);
                $this->entityManager->persist($sublayerInstance);
            }
        }
        $target->setPriority($sourceItem->getPriority());
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
     * Finds an instance layer, that is linked with a given wms source layer.
     *
     * @param WmsLayerSource $wmssourcelayer wms layer source
     * @param array $instancelayerList list of instance layers
     * @return WmsInstanceLayer|null the instance layer, otherwise null
     */
    private function findInstanceLayer(WmsLayerSource $wmssourcelayer, $instancelayerList)
    {
        foreach ($instancelayerList as $instancelayer) {
            if ($wmssourcelayer->getId() === $instancelayer->getSourceItem()->getId()) {
                return $instancelayer;
            }
        }
        return null;
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
}
