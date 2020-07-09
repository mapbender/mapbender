<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Presenter\ApplicationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Legacy remnant
 *
 * For asset collection and compilation @see \Mapbender\CoreBundle\Asset\ApplicationAssetService
 * For client-facing configuration emission @see \Mapbender\CoreBundle\Component\Presenter\Application\ConfigService
 * For Yaml application permissions setup @see \Mapbender\CoreBundle\Component\YamlApplicationImporter
 * For creating / cloning / destroying uploads directories @see \Mapbender\CoreBundle\Component\UploadsManager
 *
 * @deprecated
 * @internal
 * @author Christian Wygoda
 */
class Application
{
    /**
     * Returns the public "uploads" directory.
     * NOTE: this has nothing to with applications. Some legacy usages passed in an application
     * slug as a second argument, but it was only ever evaluated as a boolean.
     *
     * @param ContainerInterface $container
     * @param bool               $webRelative
     * @return string the path to uploads dir or null.
     * @deprecated use the uploads_manager service
     */
    public static function getUploadsDir($container, $webRelative = false)
    {
        $ulm = self::getServiceStatic($container)->getUploadsManager();
        try {

            if ($webRelative) {
                return $ulm->getWebRelativeBasePath(true);
            } else {
                return $ulm->getAbsoluteBasePath(true);
            }
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Returns the web-relative path to the application's uploads directory.
     *
     * @param ContainerInterface $container
     * @param string $slug of Application
     * @return boolean true if the application's directory already existed or has just been successfully created
     * @deprecated use the uploads_manager service
     */
    public static function getAppWebDir($container, $slug)
    {
        $ulm = static::getServiceStatic($container)->getUploadsManager();
        try {
            $ulm->getSubdirectoryPath($slug, true);
            return $ulm->getWebRelativeBasePath(false) . '/' . $slug;
        } catch (IOException $e) {
            return null;
        }
    }

    /**
     * Returns an url to application's public directory.
     *
     * @param ContainerInterface $container
     * @param string             $slug      application's slug
     * @return string a url to wmc directory or to file with "$filename"
     */
    public static function getAppWebUrl($container, $slug)
    {
        return Application::getUploadsUrl($container) . "/" . $slug;
    }

    /**
     * Returns an url to public "uploads" directory.
     *
     * @param ContainerInterface $container
     * @return string an url to public "uploads" directory
     */
    public static function getUploadsUrl($container)
    {
        $base_url = Application::getBaseUrl($container);
        return $base_url . '/' . Application::getUploadsDir($container, true);
    }

    /**
     * Returns a base url.
     *
     * @param ContainerInterface $container
     * @return string a base url
     */
    public static function getBaseUrl($container)
    {
        /** @var Request $request */
        $request = $container->get('request_stack')->getCurrentRequest();
        return $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
    }

    /**
     * @param ContainerInterface $container
     * @return ApplicationService
     */
    private static function getServiceStatic(ContainerInterface $container)
    {
        /** @var ApplicationService $service */
        $service = $container->get('mapbender.presenter.application.service');
        return $service;
    }
}
