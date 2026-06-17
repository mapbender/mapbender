<?php

namespace Mapbender\OgcApiFeaturesBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Component\Source\StyleableSourceLoaderInterface;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\Style;
use Mapbender\OgcApiFeaturesBundle\Form\Type\OgcApiFeaturesSourceType;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesSource;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesLayerSource;

class OgcApiFeaturesLoader extends SourceLoader implements StyleableSourceLoaderInterface
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
        $source->setTitle($json['title'] ?? 'OGC API - Features Source');
        $source->setDescription($json['description'] ?? '');
        $source->setVersion($version);
        $source->setAttribution($json['attribution'] ?? '');

        $this->syncLayers($source, $json['collections'] ?? [], $baseUrl);
    }

    private function syncLayers(OgcApiFeaturesSource $source, array $collections, string $baseUrl): void
    {
        $existingByCollectionId = $this->deduplicateAndIndexLayers($source);

        $activeCollectionIds = [];
        foreach ($collections as $collection) {
            $collectionId = $collection['id'];
            $activeCollectionIds[] = $collectionId;
            $bbox = !(empty($collection['extent']['spatial']['bbox'][0])) ? $collection['extent']['spatial']['bbox'][0] : null;
            $properties = $this->discoverCollectionProperties($baseUrl, $collectionId);

            if (isset($existingByCollectionId[$collectionId])) {
                $layer = $existingByCollectionId[$collectionId];
                $layer->setTitle($collection['title'] ?? '');
                $layer->setBbox($bbox);
                $layer->setProperties($properties);
            } else {
                $layer = new OgcApiFeaturesLayerSource();
                $layer->setCollectionId($collectionId);
                $layer->setTitle($collection['title'] ?? '');
                $layer->setBbox($bbox);
                $layer->setProperties($properties);
                $source->addLayer($layer);
            }
        }

        $activeSet = array_flip($activeCollectionIds);
        foreach ($source->getLayers()->toArray() as $layer) {
            /** @var OgcApiFeaturesLayerSource $layer */
            if (!isset($activeSet[$layer->getCollectionId()])) {
                $source->getLayers()->removeElement($layer);
                $this->em->remove($layer);
            }
        }
    }

    private function deduplicateAndIndexLayers(OgcApiFeaturesSource $source): array
    {
        $grouped = [];
        foreach ($source->getLayers()->toArray() as $layer) {
            /** @var OgcApiFeaturesLayerSource $layer */
            $grouped[$layer->getCollectionId()][] = $layer;
        }

        $index = [];
        foreach ($grouped as $collectionId => $layers) {
            usort($layers, static fn($a, $b) => ($a->getId() ?? \PHP_INT_MAX) <=> ($b->getId() ?? \PHP_INT_MAX));
            $kept = $layers[0];
            $index[$collectionId] = $kept;
            for ($i = 1, $c = count($layers); $i < $c; $i++) {
                foreach ($layers[$i]->getInstanceLayers() as $instanceLayer) {
                    $instanceLayer->setSourceItem($kept);
                }
                $source->getLayers()->removeElement($layers[$i]);
                $this->em->remove($layers[$i]);
            }
        }

        return $index;
    }

    private function discoverCollectionProperties(string $baseUrl, string $collectionId): ?array
    {
        // Try queryables endpoint first (preferred: gives keys + titles)
        $queryables = $this->discoverFromQueryables($baseUrl, $collectionId);
        if ($queryables !== null) {
            return $queryables;
        }

        // Fallback: fetch a single item to discover property keys
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
            $keys = array_values(array_filter($keys, fn ($k) => $k !== 'geometry'));
        } catch (\Throwable) {
            return null;
        }

        $result = [];
        foreach ($keys as $key) {
            $result[] = ['key' => $key];
        }
        return $result;
    }

    private function discoverFromQueryables(string $baseUrl, string $collectionId): ?array
    {
        $queryablesUrl = $baseUrl . '/collections/' . urlencode($collectionId) . '/queryables?f=json';
        try {
            $response = $this->httpTransport->getUrl($queryablesUrl);
            if (!$response->isOk()) {
                return null;
            }
            $data = json_decode($response->getContent(), true);
            if (!is_array($data) || empty($data['properties'])) {
                return null;
            }
            $result = [];
            foreach ($data['properties'] as $key => $info) {
                $entry = ['key' => $key];
                if (!empty($info['title'])) {
                    $entry['title'] = $info['title'];
                }
                $result[] = $entry;
            }
            return $result;
        } catch (\Throwable) {
            return null;
        }
    }

    public function loadStylesForSource(Source $source): void
    {
        /** @var OgcApiFeaturesSource $source */
        $baseUrl = rtrim($source->getJsonUrl(), '/');
        $processedCollectionIds = [];
        foreach ($source->getLayers() as $layer) {
            /** @var OgcApiFeaturesLayerSource $layer */
            $collectionId = $layer->getCollectionId();
            if (in_array($collectionId, $processedCollectionIds, true)) {
                continue;
            }
            $processedCollectionIds[] = $collectionId;
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
                    $mbsLink = $this->findMbsLink($styleInfo, $baseUrl, $collectionId);
                    if (!$mbsLink) {
                        continue;
                    }
                    $this->fetchAndSaveStyle($source, $collectionId, $styleInfo, $mbsLink);
                }
            } catch (\Throwable) {
                continue;
            }
        }
        $this->removeOrphanStyles($source, $processedCollectionIds);
    }

    private function removeOrphanStyles(OgcApiFeaturesSource $source, array $activeCollectionIds): void
    {
        if ($source->getId() === null) {
            return;
        }
        if (empty($activeCollectionIds)) {
            $orphans = $this->em->getRepository(Style::class)->findBy([
                'sourceType' => 'ogc_api',
                'sourceId' => $source->getId(),
            ]);
        } else {
            $orphans = $this->em->getRepository(Style::class)
                ->createQueryBuilder('s')
                ->where('s.sourceType = :type')
                ->andWhere('s.sourceId = :sourceId')
                ->andWhere('s.collectionId NOT IN (:collectionIds)')
                ->setParameter('type', 'ogc_api')
                ->setParameter('sourceId', $source->getId())
                ->setParameter('collectionIds', $activeCollectionIds)
                ->getQuery()
                ->getResult();
        }
        foreach ($orphans as $orphan) {
            $this->em->remove($orphan);
        }
    }

    private function findMbsLink(array $styleInfo, string $baseUrl, string $collectionId): ?string
    {
        if (!empty($styleInfo['links'])) {
            foreach ($styleInfo['links'] as $link) {
                $type = $link['type'] ?? '';
                if ($type === 'application/vnd.mapbox.style+json'
                    || str_contains($type, 'mapbox')
                    || (isset($link['href']) && str_contains($link['href'], 'f=mbs'))) {
                    return $link['href'];
                }
            }

            // Fallback from any available style endpoint link: force f=mbs.
            foreach ($styleInfo['links'] as $link) {
                if (!empty($link['href'])) {
                    return $this->withMapboxFormat($link['href']);
                }
            }
        }

        // Fallback: build URL from style id according to OGC API Styles conventions.
        $styleId = $styleInfo['id'] ?? null;
        if ($styleId) {
            return $baseUrl
                . '/collections/' . urlencode($collectionId)
                . '/styles/' . urlencode((string) $styleId)
                . '?f=mbs';
        }

        return null;
    }

    private function withMapboxFormat(string $url): string
    {
        if (preg_match('/([?&])f=[^&]*/', $url)) {
            return preg_replace('/([?&])f=[^&]*/', '${1}f=mbs', $url, 1) ?: $url;
        }
        return $url . (str_contains($url, '?') ? '&' : '?') . 'f=mbs';
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
            $remoteStyleName = $styleInfo['title'] ?? $styleInfo['id'] ?? 'style';
            $styleName = $sourceTitle . ' - ' . $layerTitle . ' - ' . $remoteStyleName;

            $style = $this->em->getRepository(Style::class)->findOneBy([
                'sourceType' => 'ogc_api',
                'sourceId' => $source->getId(),
                'collectionId' => $collectionId,
                'name' => $styleName,
            ]);

            if (!$style) {
                $style = new Style();
                $style->setSourceType('ogc_api');
                $style->setSourceId($source->getId());
                $style->setCollectionId($collectionId);
            }

            $style->setName($styleName);
            $style->setStyle($mbsContent);

            $this->em->persist($style);
        } catch (\Throwable) {
            // Skip if style cannot be fetched
        }
    }

    public function getFormType(): string
    {
        return OgcApiFeaturesSourceType::class;
    }
}
