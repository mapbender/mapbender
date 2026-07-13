<?php

namespace Mapbender\WmsBundle\Element;

use Doctrine\Persistence\ObjectRepository;
use Mapbender\WmsBundle\Element\Type\WmsLoaderAdminType;
use Doctrine\Persistence\ManagerRegistry;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * WmsLoader
 *
 * @author Karim Malhas
 * @author Paul Schmidt
 */
class WmsLoader extends AbstractElementService implements ElementHttpHandlerInterface
{

    protected ObjectRepository $instanceRepository;

    /**
     * @param string $exampleUrl
     */
    public function __construct(ManagerRegistry               $managerRegistry,
                                protected AuthorizationCheckerInterface $authorizationChecker,
                                protected TypeDirectoryService          $sourceTypeDirectory,
                                protected Importer                      $sourceImporter,
                                                              protected $exampleUrl)
    {
        $this->instanceRepository = $managerRegistry->getRepository(SourceInstance::class);
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.wms.wmsloader.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.wms.wmsloader.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            "autoOpen" => false,
            "defaultFormat" => "image/png",
            "defaultInfoFormat" => "text/html",
            "splitLayers" => false,
            "element_icon" => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbWmsLoader';
    }


    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderWmsBundle/Resources/public/MbWmsLoader.js',
            ],
            'css' => [
                '@MapbenderWmsBundle/Resources/public/sass/element/wmsloader.scss',
            ],
            'trans' => [
                'mb.wms.wmsloader.error.*',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return WmsLoaderAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderWms/ElementAdmin/wmsloader.html.twig';
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderWms/Element/wmsloader.html.twig');
        $view->attributes['class'] = 'mb-element-wmsloader';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['example_url'] = $this->exampleUrl;
        return $view;
    }

    public function getHttpHandler(Element $element): static
    {
        return $this; // :)
    }


    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        switch ($action) {
            case 'getInstances':
                $instanceIds = array_filter(explode(',', (string) $request->get('instances', '')));
                return new JsonResponse([
                    'success' => $this->getDatabaseInstanceConfigs($element, $instanceIds),
                ]);
            case 'loadWms':
                return $this->loadWms($element, $request);
            default:
                throw new NotFoundHttpException("Unknown action {$action}");
        }
    }

    protected function loadWms(Element $element, Request $request): JsonResponse
    {
        $id = "wmsloader_" . uniqid();
        $source = $this->getSource($request);
        $source->setId($id);
        /** @var WmsInstance $instance */
        $instance = $this->getSourceTypeDirectory()->getInstanceFactory($source)->createInstance($source, null);
        $instance->setId($id);
        $layerIndex = 0;
        foreach ($instance->getLayers() as $layer) {
            $layer->setId($id . '_' . $layerIndex);
            $layer->getSourceItem()->setId($id . '_' . $layerIndex);
            $layerIndex++;
        }
        $infoFormat = $request->get('infoFormat');

        $configGenerator = $this->getConfigGenerator($instance);
        $layerConfiguration = $configGenerator->getConfiguration($element->getApplication(), $instance);
        $config = array_replace(static::getDefaultConfiguration(), $element->getConfiguration());
        if ($config['splitLayers']) {
            $layerConfigurations = $this->splitLayers($layerConfiguration);
        } else {
            $layerConfigurations = [$layerConfiguration];
        }
        // amend info_format and format options
        foreach ($layerConfigurations as &$layerConfiguration) {
            $layerConfiguration['options']['info_format'] = $infoFormat ?? $config['defaultInfoFormat'];
            $layerConfiguration['options']['format'] = $config['defaultFormat'];
        }

        return new JsonResponse($layerConfigurations);
    }

    protected function getSource(Request $request): WmsSource
    {
        $origin = new HttpOriginModel();
        $origin->setOriginUrl($request->get("url"));
        $origin->setUsername($request->get("username"));
        $origin->setPassword($request->get("password"));
        /** @var WmsSource $source */
        $source = $this->sourceImporter->loadSource($origin);
        return $source;
    }

    /**
     * @return mixed[]
     */
    protected function splitLayers(array $layerConfiguration): array
    {
        $children = $layerConfiguration['configuration']['children'][0]['children'];
        $layerConfigurations = [];
        foreach ($children as $child) {
            $layerConfiguration['configuration']['children'][0]['children'] = [$child];
            $layerConfiguration['configuration']['children'][0]['options']['title'] = $child['options']['title']
                . ' ('
                . $layerConfiguration['configuration']['title']
                . ')';
            $layerConfigurations[] = $layerConfiguration;
        }
        return $layerConfigurations;
    }

    /**
     * @param string[] $instanceIds
     */
    protected function getDatabaseInstanceConfigs(Element $element, array $instanceIds): array
    {
        $instanceConfigs = [];
        foreach ($instanceIds as $instanceId) {
            /** @var SourceInstance $instance */
            $instance = $this->instanceRepository->find($instanceId);
            if ($instance && $this->authorizationChecker->isGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES)) {
                $configGenerator = $this->getConfigGenerator($instance);
                $configGenerator->preload([$instance]);
                $instanceConfigs[] = $configGenerator->getConfiguration($element->getApplication(), $instance);
            }
        }
        return $instanceConfigs;
    }

    protected function getSourceTypeDirectory()
    {
        return $this->sourceTypeDirectory;
    }

    protected function getConfigGenerator(SourceInstance $instance): SourceInstanceConfigGenerator
    {
        return $this->getSourceTypeDirectory()->getConfigGenerator($instance);
    }

    public static function getDefaultIcon(): string
    {
        return 'iconWms';
    }
}
