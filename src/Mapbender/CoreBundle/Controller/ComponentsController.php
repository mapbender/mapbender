<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\Component\AutoMimeResponseFile;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ComponentsController extends Controller
{
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

        return new BinaryFileResponse($fileInfo);
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

    protected function getPackagePath($packageName)
    {
        switch ($packageName) {
            default:
                $path = $this->getVendorPath() . "/components/{$packageName}";
                break;
            case 'bootstrap-colorpicker':
                $path = $this->getVendorPath() . "/debugteam/{$packageName}";
                break;
            case 'mapbender-icons':
                $path = $this->getVendorPath() . "/mapbender/{$packageName}";
                break;
        }
        if (\is_dir($path) && \is_readable($path)) {
            return $path;
        } else {
            return null;
        }
    }

    protected function getVendorPath()
    {
        return realpath($this->getParameter('kernel.root_dir') . '/../vendor');
    }

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
