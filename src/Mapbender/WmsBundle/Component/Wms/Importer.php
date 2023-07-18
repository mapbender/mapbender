<?php

namespace Mapbender\WmsBundle\Component\Wms;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Mapbender\Component\SourceLoader;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Component\XmlValidatorService;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser111;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser130;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use PHPUnit\Util\Exception;
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
class Importer extends SourceLoader
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

    public function getTypeCode()
    {
        return strtolower(Source::TYPE_WMS);
    }

    public function getTypeLabel()
    {
        return 'OGC WMS';
    }

    /**
     * @param $reloaded
     * @param $target
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function replaceContactByClone($reloaded, $target): void
    {
        $contact = clone $reloaded->getContact();
        if ($target->getContact()) {
            $this->entityManager->remove($target->getContact());
        }
        $target->setContact($contact);
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
        $document = $this->xmlToDom($content);
        switch ($document->documentElement->tagName) {
            // @todo: DI, handlers, prechecks
            default:
                // @todo: use a different exception to indicate lack of support
                throw new XmlParseException('mb.wms.repository.parser.not_supported_document');
            case 'WMS_Capabilities':
            case 'WMT_MS_Capabilities':
                break;
        }
        switch ($document->documentElement->getAttribute('version')) {
            default:
                throw new NotSupportedVersionException('mb.wms.repository.parser.not_supported_version');
            case '1.1.1':
                $parser = new WmsCapabilitiesParser111();
                break;
            case '1.3.0':
                $parser = new WmsCapabilitiesParser130();
                break;
        }
        $source = $parser->parse($document);
        $this->assignLayerPriorities($source->getRootlayer(), 0);
        return $source;
    }

    public function validateResponseContent($content)
    {
        $this->validator->validateDocument($this->xmlToDom($content));
    }

    /**
     * @param Source $target
     * @param Source $reloaded
     * @throws \Exception
     */
    public function updateSource(Source $target, Source $reloaded)
    {
        /** @var WmsSource $target */
        /** @var WmsSource $reloaded */
        $classMeta = $this->entityManager->getClassMetadata(ClassUtils::getClass($target));
        EntityUtil::copyEntityFields($target, $reloaded, $classMeta, false);

        $this->replaceContactByClone($reloaded, $target);

        $this->updateSourceLayers($target, $reloaded);

        $this->copyKeywords($target, $reloaded, 'Mapbender\WmsBundle\Entity\WmsSourceKeyword');
        /** @var
         * Repository $applicationRepository
         */
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
        $persistedUrl = parent::getRefreshUrl($target);
        $detectedVersion = UrlUtil::getQueryParameterCaseInsensitive($persistedUrl, 'version', null);
        if ($detectedVersion) {
            return $persistedUrl;
        } else {
            /** @var WmsSource $target */
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

    private function replaceSourceLayers(WmsSource $target, WmsSource $source)
    {
        foreach ($target->getLayers() as $oldLayer) {
            $this->entityManager->remove($oldLayer);
        }

        $target->getLayers()->clear();
        $target->getLayers()->add($source->getRootlayer());

        $this->setLayerSourceRecursive($target->getRootlayer(), $target);
    }


    /**
     * @param WmsLayerSource $layer
     * @param WmsSource $source
     */
    private function setLayerSourceRecursive(WmsLayerSource $layer, WmsSource $source)
    {
        $layer->setSource($source);
        if (!$source->getLayers()->contains($layer)) {
            $source->getLayers()->add($layer);
        }
        foreach ($layer->getSublayer() as $child) {
            $this->setLayerSourceRecursive($child, $source);
        }
    }


    public function updateSourceLayers(WmsSource $target, WmsSource $source)
    {
        $this->updateLayerSourceRecursive($target->getRootlayer(),$source->getRootlayer());
    }

    private function updateLayerSourceRecursive(WmsLayerSource $target, WmsLayerSource $source, $path = [])
    {
        static $classMeta;
        if (!isset($classMeta)) {
            $classMeta = $this->entityManager->getClassMetadata(WmsLayerSource::class);
        }
        if ($target->getName() == $source->getName()) {
            EntityUtil::copyEntityFields($target, $source, $classMeta, false);
        }
        $targetChildrenThatMustBeDeleted = $target->getSublayer()->toArray();

        foreach ($source->getSublayer() as $sourceChild) {
            $newPath = array_merge($path, [$sourceChild->getName()]);

            $correspondingTargetChildren = array_filter($target->getSublayer()->toArray(), function($targetChild) use ($sourceChild) {
                return $sourceChild->getName() == $targetChild->getName();
            });
            if (count($correspondingTargetChildren) >= 1) {
                // if there are coincidentally more than one hits, do the same as if there were only one
                $nextTargetChild = reset($correspondingTargetChildren);
                $index = array_search($nextTargetChild->getName(), array_map(function($item) {
                    return $item->getName();
                }, $targetChildrenThatMustBeDeleted));
                if ($index !== false) {
                    unset($targetChildrenThatMustBeDeleted[$index]);
                }
                $this->updateLayerSourceRecursive($nextTargetChild,$sourceChild,$newPath);
            } else if (count($correspondingTargetChildren) == 0) {
                $target->addSublayer($sourceChild);
                $this->setLayerSourceRecursive($sourceChild,$target->getSource());
            }
        }

        foreach($targetChildrenThatMustBeDeleted as $deleteCandidate) {
            // detach the layer from the source
            $deleteCandidate->setSource(null);
            $target->getSublayer()->removeElement($deleteCandidate);
        }


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

        $this->replaceInstanceLayers($instance, $source);
    }

    protected function replaceInstanceLayers(WmsInstance $instance, WmsSource $source)
    {
        $oldInstanceRoot = $instance->getRootlayer();
        // Store / "index" old instance layers so we may copy some manually
        // configured properties over
        $nameMap = array();
        $titleMap = array();
        foreach ($instance->getLayers() as $oldInstanceLayer) {
            $sourceItem = $oldInstanceLayer->getSourceItem();
            if ($sourceItem->getName()) {
                $nameMap += array($sourceItem->getName() => $oldInstanceLayer);
            }
            if ($sourceItem->getTitle()) {
                $titleMap += array($sourceItem->getTitle() => $oldInstanceLayer);
            }
        }

        // Start over
        foreach ($instance->getLayers() as $oldInstanceLayer) {
            $this->entityManager->remove($oldInstanceLayer);
        }
        $instance->getLayers()->clear();

        $newRoot = new WmsInstanceLayer();
        $newRoot->populateFromSource($instance, $source->getRootlayer());

        $instanceLayerMeta = $this->entityManager->getClassMetadata(ClassUtils::getClass($newRoot));

        // Salvage / copy previously configured instance layer properties
        foreach ($instance->getLayers() as $newInstanceLayer) {
            $copyFrom = false;
            $name = $newInstanceLayer->getSourceItem()->getName();
            $title = $newInstanceLayer->getSourceItem()->getTitle();
            if (!$newInstanceLayer->getParent()) {
                $copyFrom = $oldInstanceRoot;
            } elseif ($name && !empty($nameMap[$name])) {
                $copyFrom = $nameMap[$name];
            } elseif ($title && !empty($titleMap[$title])) {
                $copyFrom = $titleMap[$title];
            }
            if ($copyFrom) {
                // Copy all configurable properties except priority (=sorting order)
                $priority = $newInstanceLayer->getPriority();
                EntityUtil::copyEntityFields($newInstanceLayer, $copyFrom, $instanceLayerMeta);
                $newInstanceLayer->setPriority($priority);
            }
        }
    }

    /**
     * @param WmsInstance $instance
     */
    private function updateInstanceDimensions(WmsInstance $instance)
    {
        $dimensionsOld = $instance->getDimensions();
        $sourceDimensions = $instance->getSource()->getDimensions();
        $dimensions = array();
        foreach ($sourceDimensions as $sourceDimension) {
            $newDimension = null;
            foreach ($dimensionsOld as $oldDimension) {
                if ($sourceDimension->getName() === $oldDimension->getName()) {
                    // @todo: reset extent on unit change, clamp extent to updated values
                    /* replace attribute values */
                    $oldDimension->setUnits($sourceDimension->getUnits());
                    $oldDimension->setUnitSymbol($sourceDimension->getUnitSymbol());
                    $oldDimension->setNearestValue($sourceDimension->getNearestValue());
                    $oldDimension->setCurrent($sourceDimension->getCurrent());
                    $oldDimension->setMultipleValues($sourceDimension->getMultipleValues());
                    $newDimension = $oldDimension;
                    break;
                }
            }
            if (!$newDimension) {
                $newDimension = DimensionInst::fromDimension($sourceDimension);
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
}
