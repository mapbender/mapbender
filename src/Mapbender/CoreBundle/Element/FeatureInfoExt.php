<?php
/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

class FeatureInfoExt extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.featureinfoext.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.featureinfoext.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.featureinfoext.tag.feature",
            "mb.core.featureinfoext.tag.featureinfo",
            "mb.core.featureinfoext.tag.info",
            "mb.core.featureinfoext.tag.extension");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => 'Feature Info Extension',
            "map" => null,
            "featureinfo" => null,
            'load_declarative_wms' => true,
            'highlight_source' => true,
            "hits_style" => array(
                'strokeColor' => '#99FF99',
                'strokeOpacity' => 1,
                'strokeWidth' => 1,
                'strokeLinecap' => 'round',
                'strokeDashstyle' => 'solid',
                'fillColor' => "#99FF99",
                'fillOpacity' => 0.4,
                'pointRadius' => 6
            ),
            "hover_style" => array(
                'strokeColor' => '#FF9999',
                'strokeOpacity' => 1,
                'strokeWidth' => 1,
                'strokeLinecap' => 'round',
                'strokeDashstyle' => 'solid',
                'fillColor' => "#FF9999",
                'fillOpacity' => 0.4,
                'pointRadius' => 6
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbFeatureInfoExt';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\FeatureInfoExtAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.featureInfoext.js',
                'mapbender.highlighting.js'
            ),
            'css' => array(),
            'trans' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $configuration = parent::getConfiguration();
        return $this->container->get('templating')
            ->render(
                'MapbenderCoreBundle:Element:featureinfoext.html.twig',
                array(
                    'id' => $this->getId(),
                    'configuration' => $configuration,
                    'title' => $this->getTitle())
            );
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:featureinfoext.html.twig';
    }
}
