<?php

namespace Mapbender\OgcApiFeaturesBundle\Component;

use Symfony\Contracts\Translation\TranslatorInterface;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\OgcApiFeaturesBundle\Form\Type\OgcApiFeaturesSourceType;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesSource;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesLayerSource;

class OgcApiFeaturesLoader extends SourceLoader
{
    public function __construct(
        protected HttpTransportInterface $httpTransport,
        protected TranslatorInterface    $translator,
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
        $source->setAttribution($json['attribution']);

        foreach ($json['collections'] as $collection) {
            $bbox = !(empty($collection['extent']['spatial']['bbox'][0])) ? $collection['extent']['spatial']['bbox'][0] : null;
            $layer = new OgcApiFeaturesLayerSource();
            $layer->setTitle($collection['title'] ?? '');
            $layer->setCollectionId($collection['id']);
            $layer->setBbox($bbox);
            $source->addLayer($layer);
        }
    }

    public function getFormType(): string
    {
        return OgcApiFeaturesSourceType::class;
    }
}
