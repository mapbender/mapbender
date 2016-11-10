<?php
namespace Mapbender\ManagerBundle\Component;

use Mapbender\CoreBundle\Entity\Application;

/**
 * Job class
 *
 * @author Paul Schmidt
 */
class ExchangeJob
{
    const FORMAT_JSON = 'json';
    const FORMAT_YAML = 'yaml';
    protected $addAcl;
    protected $addSources;
    protected $application;
    protected $format;

    /**
     * ExchangeJob constructor.
     *
     * @param string $format
     */
    public function __construct($format = 'json')
    {
        $this->application = null;
        $this->addAcl      = false;
        $this->addSources  = false;
        if (self::FORMAT_JSON !== $format && self::FORMAT_YAML !== $format) {
            $this->format = self::FORMAT_JSON;
        } else {
            $this->format = $format;
        }
    }

    /**
     * @return bool
     */
    public function getAddAcl()
    {
        return $this->addAcl;
    }

    /**
     * @param $addAcl
     * @return $this
     */
    public function setAddAcl($addAcl)
    {
        $this->addAcl = $addAcl;
        return $this;
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
        $this->format = $format;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAddSources()
    {
        return $this->addSources;
    }

    /**
     * @param $addSources
     * @return $this
     */
    public function setAddSources($addSources)
    {
        $this->addSources = $addSources;
        return $this;
    }

    /**
     * Is format an JSON
     *
     * @return bool
     */
    public function isFormatAnJson()
    {
        return $this->format == self::FORMAT_JSON;
    }

    /**
     * Is format an YAML
     *
     * @return bool
     */
    public function isFormatAnYaml()
    {
        return $this->format == self::FORMAT_YAML;
    }
}
