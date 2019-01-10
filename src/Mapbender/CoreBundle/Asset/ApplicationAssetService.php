<?php


namespace Mapbender\CoreBundle\Asset;


use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity;
use Mapbender\ManagerBundle\Template\ManagerTemplate;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Produces merged application assets.
 * Registered in container at mapbender.application_asset.service
 */
class ApplicationAssetService
{
    /** @var ElementFactory */
    protected $elementFactory;
    /** @var RouterInterface */
    protected $router;
    /** @var ContainerInterface */
    protected $dummyContainer;
    /** @var AssetFactory */
    protected $compiler;
    /** @var bool */
    protected $minifyCss;

    public function __construct(AssetFactory $compiler,
                                ElementFactory $elementFactory,
                                RouterInterface $router,
                                $minifyCss=false)
    {
        $this->compiler = $compiler;
        $this->elementFactory = $elementFactory;
        $this->router = $router;
        $this->minifyCss = $minifyCss;
        $this->dummyContainer = new Container();
        $this->dummyContainer->set('templating', new \stdClass());
    }

    /**
     * @return string[]
     */
    public function getValidAssetTypes()
    {
        return array(
            'js',
            'css',
            'trans',
        );
    }

    /**
     * @param Entity\Application $application
     * @param string $type
     * @return string
     */
    public function getAssetContent(Entity\Application $application, $type)
    {
        if (!in_array($type, $this->getValidAssetTypes(), true)) {
            throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
        $refs = $this->collectAssetReferences($application, $type);
        return $this->compileAssetContent($application, $refs, $type);
    }

    /**
     * @param Entity\Application $application
     * @param $type
     * @return string[]
     */
    protected function collectAssetReferences(Entity\Application $application, $type)
    {
        $appComp = $this->elementFactory->appComponentFromEntity($application);
        return $appComp->getAssetGroup($type);
    }

    /**
     * @param Entity\Application $application
     * @param string[] $refs
     * @param string $type
     * @return string
     */
    protected function compileAssetContent(Entity\Application $application, $refs, $type)
    {
        switch ($type) {
            case 'css':
                $sourcePath = $this->getCssAssetSourcePath();
                $targetPath = $this->getCssAssetTargetPath($application);
                return $this->compiler->compileCss($refs, $sourcePath, $targetPath, $this->minifyCss);
            case 'js':
            case 'trans':
                return $this->compiler->compileRaw($refs);
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }

    /**
     * @param Entity\Application $application
     * @return string
     */
    protected function getCssAssetTargetPath(Entity\Application $application)
    {
        return $this->router->generate('mapbender_core_application_assets', array(
            'slug' => $application->getSlug(),
            'type' => 'css',
        ));
    }

    /**
     * @param Entity\Application $application
     * @return Template|ManagerTemplate
     */
    protected function getDummyTemplateComponent(Entity\Application $application)
    {
        $templateClassName = $application->getTemplate();
        $appComp = $this->elementFactory->appComponentFromEntity($application);
        /** @var Template|ManagerTemplate $instance */
        $instance = new $templateClassName($this->dummyContainer, $appComp);
        return $instance;
    }

    /**
     * @return string
     */
    protected function getCssAssetSourcePath()
    {
        // Calculate the subfolder under the current host that containes the web directory
        // from the Router's RequestContext.
        // This is equivalent to calling Request::getBasePath
        $baseUrl = $this->router->getContext()->getBaseUrl();
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        $beforeScript = implode('', array_slice(explode($scriptName, $baseUrl), 0, 1));
        return rtrim($beforeScript, '/') ?: '.';
    }
}
