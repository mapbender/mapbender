<?php

namespace Mapbender\OgcApiFeaturesBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\Style;
use Mapbender\OgcApiFeaturesBundle\Form\Type\OgcApiFeaturesSourceType;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesSource;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesLayerSource;

class OgcApiFeaturesLoader extends SourceLoader
{
    public function __construct(
        protected HttpTransportInterface $httpTransport,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $em,
    )
    {
    }

    public function loadSource(mixed $formData): Source
    {
        $source = new OgcApiFeaturesSource();
        $this->refreshSource($source, $formData);
        return $source;
    }

    public function refreshSource(Source $source, mixed $formData): void
    {
        /** @var OgcApiFeaturesSource $source */
        /** @var OgcApiFeaturesSource|array $formData */
        $url = is_array($formData) ? $formData['jsonUrl'] : $formData->getJsonUrl();
        $url = rtrim($url, '/');

        if (!str_ends_with($url, '/collections')) {
            $url = $url . '/collections';
        }

        $response = $this->httpTransport->getUrl($url);

        if (!$response->isOk()) {
            $statusLine = \preg_replace('#[\r\n].*$#m', '', $response->__toString());
            throw new ServerResponseErrorException($statusLine, $response->getStatusCode());
        }

        $json = json_decode($response->getContent(), true);
        if ($json === null) {
            throw new ServerResponseErrorException($this->translator->trans('mb.vectortiles.admin.error.no_json'), 401);
        }

        $version = null;
        if (preg_match('#/v(\d+(?:\.\d+)*)#', $url, $matches)) {
            $version = 'v' . $matches[1];
        }

        $baseUrl = str_replace('/collections', '', $url);
        $source->setJsonUrl($baseUrl);
        $source->setTitle($json['title'] ?? 'Ogc Api Features Source');
        $source->setDescription($json['description'] ?? '');
        $source->setVersion($version);
        $source->setAttribution($json['attribution'] ?? '');

        foreach ($json['collections'] as $collection) {
            $bbox = !(empty($collection['extent']['spatial']['bbox'][0])) ? $collection['extent']['spatial']['bbox'][0] : null;
            $layer = new OgcApiFeaturesLayerSource();
            $layer->setTitle($collection['title'] ?? '');
            $layer->setCollectionId($collection['id']);
            $layer->setBbox($bbox);
            $layer->setProperties($this->discoverCollectionProperties($baseUrl, $collection['id']));
            $source->addLayer($layer);
        }
    }

    private function discoverCollectionProperties(string $baseUrl, string $collectionId): ?array
    {
        $itemsUrl = $baseUrl . '/collections/' . urlencode($collectionId) . '/items?f=json&limit=1';
        try {
            $response = $this->httpTransport->getUrl($itemsUrl);
            if (!$response->isOk()) {
                return null;
            }
            $data = json_decode($response->getContent(), true);
            $features = $data['features'] ?? [];
            if (empty($features) || !isset($features[0]['properties'])) {
                return null;
            }
            $keys = array_keys($features[0]['properties']);
            return array_values(array_filter($keys, fn ($k) => $k !== 'geometry'));
        } catch (\Throwable) {
            return null;
        }
    }

    public function loadStylesForSource(OgcApiFeaturesSource $source): void
    {
        $baseUrl = rtrim($source->getJsonUrl(), '/');
        foreach ($source->getLayers() as $layer) {
            /** @var OgcApiFeaturesLayerSource $layer */
            $collectionId = $layer->getCollectionId();
            $stylesUrl = $baseUrl . '/collections/' . urlencode($collectionId) . '/styles?f=json';
            try {
                $response = $this->httpTransport->getUrl($stylesUrl);
                if (!$response->isOk()) {
                    continue;
                }
                $stylesJson = json_decode($response->getContent(), true);
                if (!is_array($stylesJson) || empty($stylesJson['styles'])) {
                    continue;
                }
                foreach ($stylesJson['styles'] as $styleInfo) {
                    $mbsLink = $this->findMbsLink($styleInfo);
                    if (!$mbsLink) {
                        continue;
                    }
                    $this->fetchAndSaveStyle($source, $collectionId, $styleInfo, $mbsLink);
                }
            } catch (\Throwable $e) {
                // Skip collections where style endpoint is not available
                continue;
            }
        }
    }

    private function findMbsLink(array $styleInfo): ?string
    {
        if (empty($styleInfo['links'])) {
            // Fallback: try the style id with ?f=mbs
            return null;
        }
        foreach ($styleInfo['links'] as $link) {
            $type = $link['type'] ?? '';
            if ($type === 'application/vnd.mapbox.style+json'
                || str_contains($type, 'mapbox')
                || (isset($link['href']) && str_contains($link['href'], 'f=mbs'))) {
                return $link['href'];
            }
        }
        return null;
    }

    private function fetchAndSaveStyle(
        OgcApiFeaturesSource $source,
        string $collectionId,
        array $styleInfo,
        string $mbsUrl,
    ): void {
        try {
            $response = $this->httpTransport->getUrl($mbsUrl);
            if (!$response->isOk()) {
                return;
            }
            $mbsContent = $response->getContent();
            $decoded = json_decode($mbsContent, true);
            if (!is_array($decoded)) {
                return;
            }

            $sourceTitle = $source->getTitle() ?: (string) $source->getId();
            // Find the layer title for the collection
            $layerTitle = $collectionId;
            foreach ($source->getLayers() as $layer) {
                /** @var OgcApiFeaturesLayerSource $layer */
                if ($layer->getCollectionId() === $collectionId && $layer->getTitle()) {
                    $layerTitle = $layer->getTitle();
                    break;
                }
            }
            $styleName = $sourceTitle . ' - ' . $layerTitle;

            $style = new Style();
            $style->setName($styleName);
            $style->setStyle($mbsContent);
            $style->setSourceType('ogc_api');
            $style->setSourceId($source->getId());
            $style->setCollectionId($collectionId);

            $this->em->persist($style);
        } catch (\Throwable $e) {
            // Skip if style cannot be fetched
        }
    }

    public function getFormType(): string
    {
        return OgcApiFeaturesSourceType::class;
    }
}
