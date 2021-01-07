<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\CoreBundle\Entity\Application;

/**
 * Form model for ExportJobType
 */
class ExportJob extends ExchangeJob
{
    protected $application;
    protected $format;

    /**
     * ExchangeJob constructor.
     *
     * @param string $format
     */
    public function __construct($format = null)
    {
        $this->setFormat($format ?: static::FORMAT_JSON);
    }

    /**
     * @return Application|null
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Application $application
     * @return $this
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param $format
     * @return $this
     */
    public function setFormat($format)
    {
        if (self::FORMAT_JSON !== $format && self::FORMAT_YAML !== $format) {
            throw new \InvalidArgumentException("Unsupported format " . print_r($format, true));
        }
        $this->format = $format;
        return $this;
    }
}
