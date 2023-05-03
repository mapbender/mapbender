<?php


namespace Mapbender\FrameworkBundle\Listener;


use Mapbender\CoreBundle\Entity\Application;

class ApplicationEngineListener
{
    public function postLoad(Application $application)
    {
        // Rewrite legacy explicit 'ol4' identifier to 'current'
        if ('ol4' === $application->getMapEngineCode()) {
            $application->setMapEngineCode(Application::MAP_ENGINE_CURRENT);
        }
    }
}
