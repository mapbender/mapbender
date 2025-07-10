<?php

namespace Mapbender\VectorTilesBundle\Component;


use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;
use Mapbender\VectorTilesBundle\Type\VectorTileSourceType;
use Symfony\Contracts\Translation\TranslatorInterface;

class VectorTilesLoader extends SourceLoader
{

    public function __construct(
        protected HttpTransportInterface $httpTransport,
        protected TranslatorInterface    $translator,
    )
    {
    }

    public function loadSource(mixed $formData): Source
    {
        $source = new VectorTileSource();
        $this->refreshSource($source, $formData);
        return $source;
    }

    public function refreshSource(Source $source, mixed $formData): void
    {
        /** @var VectorTileSource $source */
        /** @var VectorTileSource|array $formData */
        $url = is_array($formData) ? $formData['jsonUrl'] : $formData->getJsonUrl();
        $response = $this->httpTransport->getUrl($url);

        if (!$response->isOk()) {
            // __toString is the only way to access the statusText property :(
            $statusLine = \preg_replace('#[\r\n].*$#m', '', $response->__toString());
            throw new ServerResponseErrorException($statusLine, $response->getStatusCode());
        }

        $json = json_decode($response->getContent(), true);
        if ($json === null) {
            throw new ServerResponseErrorException($this->translator->trans("mb.vectortiles.admin.error.no_json"), 401);
        }
        $source->setJsonUrl($url);
        $source->setTitle($json['name'] ?? $json['id'] ?? 'Vector Tile Source');
        $source->setDescription($json['description'] ?? '');
        $source->setVersion($json['version'] ?? '');
        $source->setBbox($this->loadBbox($json));
    }

    public function getFormType(): string
    {
        return VectorTileSourceType::class;
    }

    private function loadBbox(array $styleJson): ?array
    {
        if (!isset($styleJson['sources'])) return null;

        $bbox = null;

        foreach ($styleJson['sources'] as $source) {
            if (!isset($source['url']) || !isset($source['type']) || $source['type'] !== 'vector') {
                continue;
            }

            $response = $this->httpTransport->getUrl($source['url']);

            if (!$response->isOk()) {
                // __toString is the only way to access the statusText property :(
                $statusLine = \preg_replace('#[\r\n].*$#m', '', $response->__toString());
                throw new ServerResponseErrorException($statusLine . " while retrieving " . $source['url'], $response->getStatusCode());
            }

            $tileJson = json_decode($response->getContent(), true);
            if ($tileJson === null) continue;

            if (isset($tileJson['bounds']) && is_array($tileJson['bounds'])) {
                if ($bbox === null) {
                    $bbox = $tileJson['bounds'];
                } else {
                    // Merge bounding boxes
                    $bbox[0] = min($bbox[0], $tileJson['bounds'][0]);
                    $bbox[1] = min($bbox[1], $tileJson['bounds'][1]);
                    $bbox[2] = max($bbox[2], $tileJson['bounds'][2]);
                    $bbox[3] = max($bbox[3], $tileJson['bounds'][3]);
                }
            }
        }

        return $bbox;
    }
}
