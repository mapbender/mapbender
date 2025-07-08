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
        protected TranslatorInterface $translator,
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
        /** @var VectorTileSource|array $url */
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
    }

    public function getFormType(): string
    {
        return VectorTileSourceType::class;
    }
}
