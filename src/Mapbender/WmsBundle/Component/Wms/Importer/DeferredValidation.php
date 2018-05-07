<?php


namespace Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Component\Wms\Importer;


/**
 * Capture an XML validation task to support lazy evaluation.
 *
 * XML validation of certain documents with complex include structures can take a looooong time, so in scenarios where
 * we completely ignore the validation result, we can skip past a lot of overhead transparently by simply deferring
 * the whole validation process.
 */
class DeferredValidation
{
    /** @var Source */
    protected $source;

    /** @var \DOMDocument */
    protected $document;

    /** @var Importer */
    protected $service;

    public function __construct(Source $source, \DOMDocument $document, Importer $service)
    {
        $this->source = $source;
        $this->document = $document;
        $this->service = $service;
    }

    /**
     * @return XmlParseException|null
     */
    public function run()
    {
        try {
            $this->service->validate($this->document);
            $this->source->setValid(true);
            return null;
        } catch (XmlParseException $e) {
            $this->source->setValid(false);
            return $e;
        }
    }
}
