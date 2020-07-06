<?php

namespace Mapbender\Component\Loader;

use Mapbender\CoreBundle\Entity\Source;

class SourceLoaderResponse
{
    /** @var Source */
    protected $source;
    /** @var \DOMDocument */
    protected $document;

    /**
     * SourceLoaderResponse constructor.
     * @param Source $source
     */
    public function __construct(Source $source)
    {
        $this->source = $source;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }
}
