<?php


namespace Mapbender\FrameworkBundle\Listener;


use Mapbender\CoreBundle\Entity\Application;

class ApplicationEngineListener
{
    protected $forceEngine;

    public function __construct($forceEngine)
    {
        $this->forceEngine = $forceEngine;
    }

    public function postLoad(Application $application)
    {
        if ($this->forceEngine) {
            $application->setMapEngineCode($this->forceEngine);
        }
    }
}
