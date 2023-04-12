<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\Component\AutoMimeResponseFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to deliver assets from various vendor paths from /components/ urls.
 * Only answers if file does not actually exist in web/components (see example rewrite configuration in .htaccess).
 * Having this Controller allows installing and requesting /components/ packages even without having
 * a "component installer" package, such as robloach/component-installer (abandoned) or
 * mnsami/composer-custom-directory-installer on the system.
 */
class ComponentsController
{
    protected $webRoot;
    protected $vendorRoot;

    public function __construct($webRoot, $vendorRoot)
    {
        $this->webRoot = realpath($webRoot);
        $this->vendorRoot = realpath($vendorRoot);
    }

    /**
     * @Route("/components/{packageName}/{path}", methods={"GET"}, requirements={"path"=".+"})
     * @param Request $request
     * @param string $packageName
     * @param string $path
     * @return Response
     */
    public function componentsAction(Request $request, $packageName, $path)
    {
        if ($this->matchHidden($path)) {
            throw new NotFoundHttpException();
        }
        $fileInfo = $this->locateFile($packageName, $path);
        if (!$fileInfo) {
            throw new NotFoundHttpException();
        }
        $response = new BinaryFileResponse($fileInfo);
        $response->isNotModified($request);
        return $response;
    }

    /**
     * @param string $packageName
     * @param string $filePath
     * @return \SplFileInfo|null
     */
    protected function locateFile($packageName, $filePath)
    {
        $packagePath = $this->getPackagePath($packageName);
        if ($packagePath) {
            $fullPath = "{$packagePath}/{$filePath}";

            if (\is_readable($fullPath) && !\is_dir($fullPath)) {
                return new AutoMimeResponseFile($fullPath);
            }
        }
        return null;
    }

    /**
     * @param string $packageName
     * @return string|null
     */
    protected function getPackagePath($packageName)
    {
        switch ($packageName) {
            default:
                $path = $this->getVendorPath() . "/components/{$packageName}";
                break;
            case 'bootstrap-colorpicker':
            case 'jquery-ui-touch-punch':
                $path = $this->getWebPath() . "/bundles/mapbendercore/{$packageName}";
                break;
            case 'mapbender-icons':
                $path = $this->getVendorPath() . "/mapbender/{$packageName}";
                break;
            case 'open-sans':
                $path = $this->getVendorPath() . "/wheregroup/{$packageName}";
                break;
        }
        if (\is_dir($path) && \is_readable($path)) {
            return $path;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    protected function getVendorPath()
    {
        return $this->vendorRoot;
    }

    protected function getWebPath()
    {
        return $this->webRoot;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function matchHidden($path)
    {
        $patterns = array(
            '#(^|/)\.#',
            '#(^|/)(composer|component|package|bower).json$#',
            '#(^|/)[^/]+\.(md|txt)$#',
            '#(^|/)Makefile[^/]*$#',
        );
        foreach ($patterns as $pattern) {
            if (\preg_match($pattern, $path)) {
                return true;
            }
        }
        return false;
    }
}
