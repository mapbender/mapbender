<?php


namespace Mapbender\CoreBundle\Extension;


use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Asset\PathPackage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApplicationExtension extends AbstractExtension
{
    /** @var string */
    protected $baseDirectory;
    /** @var string */
    protected $baseUrlPath;

    /**
     * @param UploadsManager $uploadsManager
     * @param PathPackage $pathPackage
     */
    public function __construct(UploadsManager $uploadsManager, PathPackage $pathPackage)
    {
        $this->baseDirectory = $uploadsManager->getAbsoluteBasePath(false);
        $this->baseUrlPath = $pathPackage->getUrl($uploadsManager->getWebRelativeBasePath(false));
    }

    public function getName()
    {
        return 'mapbender_application';
    }

    public function getFunctions()
    {
        return array(
            'application_screenshot_path' => new TwigFunction('application_screenshot_path', array($this, 'application_screenshot_path')),
        );
    }

    /** @noinspection PhpUnused */
    /**
     * @param Application $application
     * @return string|null
     */
    public function application_screenshot_path(Application $application)
    {
        if ($application->getSlug() && $application->getScreenshot() && $this->baseDirectory) {
            $filePath = "{$this->baseDirectory}/{$application->getSlug()}/{$application->getScreenshot()}";
            if (@\file_exists($filePath)) {
                return "{$this->baseUrlPath}/{$application->getSlug()}/{$application->getScreenshot()}";
            }
        }
        return null;
    }
}
