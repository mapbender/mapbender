<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ImportAwareInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatingElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class Overview extends AbstractElementService implements FloatingElement, ImportAwareInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.overview.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.overview.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'layerset' => null,
            'width' => 200,
            'height' => 100,
            'anchor' => 'right-top',
            'maximized' => true,
            'fixed' => false,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbOverview';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\OverviewAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.overview.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/overview.scss',
            ),
            'trans' => array(
                'mb.core.overview.nolayer',
            ),
        );
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:overview.html.twig');
        $view->attributes['class'] = 'mb-element-overview';
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:overview.html.twig';
    }

    public function onImport(Element $element, Mapper $mapper)
    {
        $configuration = $element->getConfiguration();
        if (isset($configuration['layerset'])) {
            $configuration['layerset'] = $mapper->getIdentFromMapper(
                'Mapbender\CoreBundle\Entity\Layerset',
                $configuration['layerset'],
                true
            );
            $element->setConfiguration($configuration);
        }
    }
}
