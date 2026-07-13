<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\Component\AutoMimeResponseFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to deliver assets from various vendor paths from /components/ urls.
 * Only answers if file does not actually exist in public/components (see example rewrite configuration in .htaccess).
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
     * @param Request $request
     * @param string $packageName
     * @param string $path
     * @return Response
     */
    #[Route(path: '/components/{packageName}/{path}', requirements: ['path' => '.+'], methods: ['GET'])]
    public function componentsAction(Request $request, $packageName, $path): BinaryFileResponse
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
    protected function locateFile($packageName, $filePath): ?AutoMimeResponseFile
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
    protected function getPackagePath($packageName): ?string
    {
        $path = match ($packageName) {
            'bootstrap-colorpicker', 'jquery-ui-touch-punch' => $this->getWebPath() . "/bundles/mapbendercore/{$packageName}",
            'mapbender-icons' => $this->getVendorPath() . "/mapbender/{$packageName}",
            'open-sans' => $this->getVendorPath() . "/wheregroup/{$packageName}",
            default => $this->getVendorPath() . "/components/{$packageName}",
        };
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
    protected function matchHidden($path): bool
    {
        $patterns = [
            '#(^|/)\.#',
            '#(^|/)(composer|component|package|bower).json$#',
            '#(^|/)[^/]+\.(md|txt)$#',
            '#(^|/)Makefile[^/]*$#',
        ];
        foreach ($patterns as $pattern) {
            if (\preg_match($pattern, $path)) {
                return true;
            }
        }
        return false;
    }
}
