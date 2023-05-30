<?php


namespace Mapbender\Component\Event;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Contracts\EventDispatcher\Event;

class ApplicationEvent extends Event
{
    /**
     * Dispatched under this name immediately before frontend application configuration is generated.
     * @see \Mapbender\CoreBundle\Component\Presenter\Application\ConfigService::getConfiguration()
     */
    const EVTNAME_BEFORE_CONFIG = 'mb.before_application_config';

    /** @var Application */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }
}
