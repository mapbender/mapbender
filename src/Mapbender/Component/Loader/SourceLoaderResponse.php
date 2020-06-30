<?php

namespace Mapbender\Component\Loader;

use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Component\Wms\Importer\DeferredValidation;

class SourceLoaderResponse
{
    /** @var Source */
    protected $source;
    /** @var \DOMDocument */
    protected $document;
    /** @var DeferredValidation|XmlParseException|null */
    protected $validationError;

    /**
     * SourceLoaderResponse constructor.
     * @param Source $source
     * @param \DOMDocument $document
     * @param DeferredValidation|\Exception|null $validationError
     */
    public function __construct(Source $source, \DOMDocument $document, $validationError = null)
    {
        $this->source = $source;
        $this->document = $document;
        $this->validationError = $validationError;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return \DOMDocument
     */
    public function getDocument()
    {
        return $this->document;
    }
}
