<?php


namespace Mapbender\WmsBundle\Component\Wms\Importer;


use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\WmsBundle\Entity\WmsSource;

class Response
{
    protected $wmsSourceEntity;
    protected $validationError;

    public function __construct(WmsSource $wmsSourceEntity, XmlParseException $validationError = null)
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
        return $this->validationError;
    }
}
