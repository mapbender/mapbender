<?php

namespace Mapbender\VectorTilesBundle\Component;


use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;
use Mapbender\VectorTilesBundle\Type\VectorTileSourceType;

class VectorTilesLoader extends SourceLoader
{

    public function __construct(
        protected HttpTransportInterface $httpTransport,
    )
    {
    }

    public function loadSource(mixed $formData): Source
    {
        $response = $this->httpTransport->getUrl($formData['jsonUrl']);

        if (!$response->isOk()) {
            // __toString is the only way to access the statusText property :(
            $statusLine = \preg_replace('#[\r\n].*$#m', '', $response->__toString());
            throw new ServerResponseErrorException($statusLine, $response->getStatusCode());
        }

        $json = json_decode($response->getContent(), true);
        $source = new VectorTileSource();
        $source->setJsonUrl($formData['jsonUrl']);
        $source->setTitle($json['name'] ?? $json['id'] ?? 'Vector Tile Source');
        $source->setDescription($json['description'] ?? '');
        $source->setVersion($json['version'] ?? '');
        return $source;
    }

    public function getFormType(): string
    {
        return VectorTileSourceType::class;
    }
}
