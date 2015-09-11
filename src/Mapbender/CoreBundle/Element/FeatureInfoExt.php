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
    public function getConfiguration()
    {
        $config = parent::getConfiguration();
        /*if (!isset($config['width']) || !$config['width']) {
            $default = self::getDefaultConfiguration();
            $config['width'] = $default['width'];
        }

        if (!isset($config['height']) || !$config['height']) {
            $default = $default ? $default : self::getDefaultConfiguration();
            $config['height'] = $default['height'];
        }*/
        return $config;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => 'Feature Info Extension',
            "target" => null,
            'highlightSource' => true,
            'loadWms' => true
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