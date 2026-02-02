<?php

namespace Mapbender\WmsBundle\Element;

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

    /** @var \Doctrine\Persistence\ObjectRepository */
    protected $instanceRepository;
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;
    /** @var TypeDirectoryService */
    protected $sourceTypeDirectory;
    /** @var Importer */
    protected $sourceImporter;

    /** @var string */
    protected $exampleUrl;

    public function __construct(ManagerRegistry               $managerRegistry,
                                AuthorizationCheckerInterface $authorizationChecker,
                                TypeDirectoryService          $sourceTypeDirectory,
                                Importer                      $sourceImporter,
                                                              $exampleUrl)
    {
        $this->instanceRepository = $managerRegistry->getRepository(SourceInstance::class);
        $this->authorizationChecker = $authorizationChecker;
        $this->sourceTypeDirectory = $sourceTypeDirectory;
        $this->sourceImporter = $sourceImporter;
        $this->exampleUrl = $exampleUrl;
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.wms.wmsloader.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.wms.wmsloader.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "autoOpen" => false,
            "defaultFormat" => "image/png",
            "defaultInfoFormat" => "text/html",
            "splitLayers" => false,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbWmsloader';
    }


    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderWmsBundle/Resources/public/mapbender.element.wmsloader.js',
            ),
            'css' => array(
                '@MapbenderWmsBundle/Resources/public/sass/element/wmsloader.scss',
            ),
            'trans' => array(
                'mb.wms.wmsloader.error.*',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmsBundle\Element\Type\WmsLoaderAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderWms/ElementAdmin/wmsloader.html.twig';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderWms/Element/wmsloader.html.twig');
        $view->attributes['class'] = 'mb-element-wmsloader';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['example_url'] = $this->exampleUrl;
        return $view;
    }

    public function getHttpHandler(Element $element)
    {
        return $this; // :)
    }


    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        switch ($action) {
            case 'getInstances':
                $instanceIds = array_filter(explode(',', $request->get('instances', '')));
                return new JsonResponse(array(
                    'success' => $this->getDatabaseInstanceConfigs($element, $instanceIds),
                ));
            case 'loadWms':
                return $this->loadWms($element, $request);
            default:
                throw new NotFoundHttpException("Unknown action {$action}");
        }
    }

    protected function loadWms(Element $element, Request $request)
    {
        $id = "wmsloader_" . uniqid();
        $source = $this->getSource($request);
        $source->setId($id);
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
        $config = array_replace($this->getDefaultConfiguration(), $element->getConfiguration());
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
        return $this->sourceImporter->loadSource($origin);
    }

    protected function splitLayers($layerConfiguration)
    {
        $children = $layerConfiguration['configuration']['children'][0]['children'];
        $layerConfigurations = array();
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
        $instanceConfigs = array();
        foreach ($instanceIds as $instanceId) {
            /** @var SourceInstance $instance */
            $instance = $this->instanceRepository->find($instanceId);
            if ($instance && $this->authorizationChecker->isGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES)) {
                $instanceConfigs[] = $this->getConfigGenerator($instance)->getConfiguration($element->getApplication(), $instance);
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
}
