<?php


namespace Mapbender\WmtsBundle\Component\Wmts;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\Component\SourceLoader;
use Mapbender\Component\SourceLoaderSettings;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\XmlValidatorService;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\WmtsBundle\Component\InstanceFactoryWmts;
use Mapbender\WmtsBundle\Component\TmsCapabilitiesParser100;
use Mapbender\WmtsBundle\Component\WmtsCapabilitiesParser100;
use Mapbender\WmtsBundle\Entity\HttpTileSource;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsSourceKeyword;

class Loader extends SourceLoader
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var XmlValidatorService */
    protected $validator;

    public function __construct(EntityManagerInterface $entityManager,
                                HttpTransportInterface $httpTransport,
                                XmlValidatorService $validator)
    {
        parent::__construct($httpTransport);
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function getTypeCode()
    {
        // HACK: do not show separate Wmts + Tms type choices
        //       when loading a new source
        return strtolower(Source::TYPE_WMTS);
    }

    public function getTypeLabel()
    {
        // HACK: do not show separate Wmts + Tms type choices
        //       when loading a new source
        return 'OGC WMTS / TMS';
    }

    /**
     * @throws \Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException
     * @throws XmlParseException
     * @throws ServerResponseErrorException
     * @return HttpTileSource
     */
    public function parseResponseContent($content)
    {
        $doc = $this->xmlToDom($content);
        switch ($doc->documentElement->tagName) {
            // @todo: DI, handlers, prechecks
            default:
                // @todo: use a different exception to indicate lack of support
                throw new XmlParseException('mb.wms.repository.parser.not_supported_document');
            case 'TileMapService':
                $parser = new TmsCapabilitiesParser100($this->httpTransport);
                return $parser->parse($doc);
            case 'Capabilities':
                $parser = new WmtsCapabilitiesParser100();
                return $parser->parse($doc);
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidUrlException
     */
    protected function getResponse(HttpOriginInterface $origin)
    {
        $url = $origin->getOriginUrl();
        static::validateUrl($url);
        $url = UrlUtil::addCredentials($url, $origin->getUsername(), $origin->getPassword());
        return $this->httpTransport->getUrl($url);
    }

    public function validateResponseContent($content)
    {
        $this->validator->validateDocument($this->xmlToDom($content));
    }

    public function updateSource(Source $target, Source $reloaded, ?SourceLoaderSettings $settings = null)
    {
        /** @var HttpTileSource $target */
        /** @var HttpTileSource $reloaded */
        if ($target->getContact()) {
            $this->entityManager->remove($target->getContact());
        }
        $target->setContact(clone ($reloaded->getContact()));

        $this->replaceSourceLayers($target, $reloaded);

        KeywordUpdater::updateKeywords($target, $reloaded, $this->entityManager, WmtsSourceKeyword::class);

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

    protected function replaceSourceLayers(HttpTileSource $target, HttpTileSource $source)
    {
        foreach ($target->getLayers() as $old) {
            $this->entityManager->remove($old);
        }
        $target->getLayers()->clear();
        foreach ($source->getLayers() as $layer) {
            $target->addLayer($layer);
        }
    }

    protected function updateInstance(WmtsInstance $instance)
    {
        $identifierMap = array();
        foreach ($instance->getLayers() as $instanceLayer) {
            $identifier = $instanceLayer->getSourceItem()->getIdentifier();
            $identifierMap[$identifier] = $instanceLayer;
            $this->entityManager->remove($instanceLayer);
        }
        $instance->getLayers()->clear();
        $instanceLayerMeta = $this->entityManager->getClassMetadata(WmtsInstanceLayer::class);

        foreach ($instance->getSource()->getLayers() as $sourceLayer) {
            $identifier = $sourceLayer->getIdentifier();
            $newInstanceLayer = InstanceFactoryWmts::createInstanceLayer($sourceLayer);
            if (!empty($identifierMap[$identifier])) {
                // Copy previous instance layer settings
                EntityUtil::copyEntityFields($newInstanceLayer, $identifierMap[$identifier], $instanceLayerMeta);
            }
            $instance->addLayer($newInstanceLayer);
        }
    }
}
