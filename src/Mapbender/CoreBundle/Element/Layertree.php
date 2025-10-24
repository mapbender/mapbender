<?php

namespace Mapbender\CoreBundle\Element;

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
    public static function getClassTitle()
    {
        return "mb.core.layertree.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.layertree.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbLayertree';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LayertreeAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        $assets = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/dragdealer.min.js',
                // For nested layer toggling in source view
                '@MapbenderCoreBundle/Resources/public/widgets/content-toggle.js',
                '@MapbenderWmsBundle/Resources/public/mapbender.wms.dimension.js',
                '@MapbenderCoreBundle/Resources/public/elements/MbLayertree.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/layertree.scss',
            ),
            'trans' => array(
                'mb.core.layertree.*',
                'mb.core.metadata.*',
                'mb.demoapps.*',
            ),
        );
        return $assets;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "autoOpen" => false,
            "showBaseSource" => true,
            "hideInfo" => false,
            "menu" => array(),
            "useTheme" => false,
            'allowReorder' => true,
            'themes' => array(),
            'showFilter' => false,
            'element_icon' => self::getDefaultIcon(),
        );
    }

    public function getView(Element $element)
    {
        $view = new TemplateView($this->getTwigTemplatePath());
        $view->attributes['class'] = 'mb-element-layertree';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['configuration'] = array(
            'menu' => $element->getConfiguration()['menu'],
            'showFilter' => $element->getConfiguration()['showFilter'],
        );
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderCore/ElementAdmin/layertree.html.twig';
    }


    /**
     * @inheritdoc
     */
    public function onImport(Element $element, Mapper $mapper)
    {
        $configuration = $element->getConfiguration();
        if (!empty($configuration['themes'])) {
            foreach ($configuration['themes'] as $k => $themeConfig) {
                $oldLsId = $themeConfig['id'];
                $newLsId = $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\Layerset', $oldLsId, true);
                // Must cast to string; entities may return numeric ids during duplication,
                // but all ids loaded by doctrine will be strings.
                $configuration['themes'][$k]['id'] = strval($newLsId);
            }
            $element->setConfiguration($configuration);
        }
    }

    public function getClientConfiguration(Element $element)
    {
        $config = parent::getClientConfiguration($element) + array('menu' => array());
        // Force menu to a list of strings (= JavaScript Array, never Object)
        $config['menu'] = \array_values($config['menu']);
        return $config;
    }

    public function getTwigTemplatePath(): string
    {
        return '@MapbenderCore/Element/layertree.html.twig';
    }

    public static function getDefaultIcon()
    {
        return 'icon-layer-tree';
    }
}
