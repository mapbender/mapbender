<?php


namespace Mapbender\WmsBundle\Component\Wms\Importer;


use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\WmsBundle\Entity\WmsSource;

class Response
{
    protected $wmsSourceEntity;
    /** @var DeferredValidation|XmlParseException|null */
    protected $validationError;

    public function __construct(WmsSource $wmsSourceEntity, $validationError = null)
    {
        $this->wmsSourceEntity = $wmsSourceEntity;
        $this->validationError = $validationError;
    }

    /**
     * @return WmsSource
     */
    public function getWmsSourceEntity()
    {
        return $this->wmsSourceEntity;
    }

    /**
     * @return XmlParseException|null
     */
    public function getValidationError()
    {
        if ($this->validationError && ($this->validationError instanceof DeferredValidation)) {
            // evaluate validation now, replace proxy with result
            $this->validationError = $this->validationError->run();
        }
        return $this->validationError;
    }
}
