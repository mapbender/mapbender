<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApplicationRegionTitleExtension extends AbstractExtension
{
    /** @var ApplicationTemplateRegistry */
    protected $templateRegistry;

    public function __construct(ApplicationTemplateRegistry $templateRegistry)
    {
        $this->templateRegistry = $templateRegistry;
    }

    public function getFunctions()
    {
        return array(
            'application_region_title' => new TwigFunction('application_region_title', array($this, 'application_region_title')),
        );
    }

    public function application_region_title(Application $application, $regionName)
    {
        $template = $this->templateRegistry->getApplicationTemplate($application);
        return $template::getRegionTitle($regionName);
    }
}
