<?php


namespace Mapbender\Component\Event;


use Mapbender\CoreBundle\Entity\Application;

class ApplicationConfigEvent extends ApplicationEvent
{
    /**
     * Dispatched under this name after frontend application configuration is generated.
     * @see \Mapbender\CoreBundle\Component\Presenter\Application\ConfigService::getConfiguration()
     */
    const EVTNAME_AFTER_CONFIG = 'mb.after_application_config';

    /** @var mixed[] */
    protected $configuration;

    public function __construct(Application $application, array $configuration)
    {
        parent::__construct($application);
        $this->configuration = $configuration;
    }

    /**
     * @return mixed[]
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param mixed[] $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }
}
