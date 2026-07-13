<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApplicationRegionTitleExtension extends AbstractExtension
{
    public function __construct(protected ApplicationTemplateRegistry $templateRegistry)
    {
    }

    public function getFunctions(): array
    {
        return [
            'application_region_title' => new TwigFunction('application_region_title', $this->application_region_title(...)),
        ];
    }

    public function application_region_title(Application $application, $regionName)
    {
        $template = $this->templateRegistry->getApplicationTemplate($application);
        return $template::getRegionTitle($regionName);
    }
}
