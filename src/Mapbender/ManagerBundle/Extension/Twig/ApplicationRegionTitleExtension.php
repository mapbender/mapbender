<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApplicationRegionTitleExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            'application_region_title' => new TwigFunction('application_region_title', array($this, 'application_region_title')),
        );
    }

    public function application_region_title(Application $application, $regionName)
    {
        /** @var Template|string $tpl */
        $tpl = $application->getTemplate();
        if ($tpl) {
            return $tpl::getRegionTitle($regionName);
        } else {
            return \ucfirst($regionName);
        }
    }
}
