<?php

namespace Mapbender\VectorTilesBundle\Component;


use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Component\Source\SourceLoaderSettings;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Component\HttpFoundation\Response;

class VectorTilesLoader extends SourceLoader
{

    protected function getResponse(HttpOriginInterface $origin): Response
    {
        throw new \Exception("Not yet implemented");
    }

    public function parseResponseContent($content): Source
    {
        throw new \Exception("Not yet implemented");
    }

    public function validateResponseContent(string $content): void
    {
        throw new \Exception("Not yet implemented");
    }

    public function updateSource(Source $target, Source $reloaded, ?SourceLoaderSettings $settings = null)
    {
        throw new \Exception("Not yet implemented");
    }
}
