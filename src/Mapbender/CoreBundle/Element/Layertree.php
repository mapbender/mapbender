<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\LayertreeAdminType;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ImportAwareInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Component\Mapper;

class Layertree extends AbstractElementService implements ImportAwareInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.layertree.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.layertree.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbLayertree';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return LayertreeAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        $assets = [
            'js' => [
                '@FOMCoreBundle/Resources/public/js/dragdealer.min.js',
                // For nested layer toggling in source view
                '@MapbenderCoreBundle/Resources/public/widgets/content-toggle.js',
                '@MapbenderWmsBundle/Resources/public/mapbender.wms.dimension.js',
                '@MapbenderCoreBundle/Resources/public/elements/MbLayertree.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/layertree.scss',
            ],
            'trans' => [
                'mb.core.layertree.*',
                'mb.core.metadata.*',
                'mb.demoapps.*',
            ],
        ];
        return $assets;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            "autoOpen" => false,
            "showBaseSource" => true,
            "hideInfo" => false,
            "menu" => [],
            "useTheme" => false,
            'allowReorder' => true,
            'themes' => [],
            'showFilter' => false,
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView($this->getTwigTemplatePath());
        $view->attributes['class'] = 'mb-element-layertree';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['configuration'] = [
            'menu' => $element->getConfiguration()['menu'],
            'showFilter' => $element->getConfiguration()['showFilter'],
        ];
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/layertree.html.twig';
    }


    /**
     * @inheritdoc
     */
    public function onImport(Element $element, Mapper $mapper): void
    {
        $configuration = $element->getConfiguration();
        if (!empty($configuration['themes'])) {
            foreach ($configuration['themes'] as $k => $themeConfig) {
                $oldLsId = $themeConfig['id'];
                $newLsId = $mapper->getIdentFromMapper(Layerset::class, $oldLsId, true);
                // Must cast to string; entities may return numeric ids during duplication,
                // but all ids loaded by doctrine will be strings.
                $configuration['themes'][$k]['id'] = strval($newLsId);
            }
            $element->setConfiguration($configuration);
        }
    }

    /**
     * @return mixed[]
     */
    public function getClientConfiguration(Element $element): array
    {
        $config = parent::getClientConfiguration($element) + ['menu' => []];
        // Force menu to a list of strings (= JavaScript Array, never Object)
        $config['menu'] = \array_values($config['menu']);
        return $config;
    }

    public function getTwigTemplatePath(): string
    {
        return '@MapbenderCore/Element/layertree.html.twig';
    }

    public static function getDefaultIcon(): string
    {
        return 'icon-layer-tree';
    }
}
