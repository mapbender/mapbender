<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateAssetDependencyInterface;
use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateInterface;
use Mapbender\CoreBundle\Entity\RegionProperties;

/**
 * Defines twig template and asset dependencies and regions for an Application template.
 * Also defines the displayable title of the template that is displayed in the backend when choosing or
 * displaying the template assigned to an Application.
 *
 * @author Christian Wygoda
 */
abstract class Template implements IApplicationTemplateInterface, IApplicationTemplateAssetDependencyInterface
{
    const OVERLAY_ANCHOR_LEFT_TOP = 'left-top';
    const OVERLAY_ANCHOR_RIGHT_TOP = 'right-top';
    const OVERLAY_ANCHOR_LEFT_BOTTOM = 'left-bottom';
    const OVERLAY_ANCHOR_RIGHT_BOTTOM = 'right-bottom';

    // pure descriptor class
    final public function __construct() {}

    public function getVariablesAssets()
    {
        return array(
            '@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAssets($type)
    {
        switch ($type) {
            case 'js':
            case 'css':
            case 'trans':
                return array();
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }

    public function getRegionTemplate(\Mapbender\CoreBundle\Entity\Application $application, $regionName)
    {
        switch ($regionName) {
            case 'sidepane':
                return '@MapbenderCore/Template/region/sidepane.html.twig';
            case 'toolbar':
                return '@MapbenderCore/Template/region/toolbar.html.twig';
            case 'footer':
                return '@MapbenderCore/Template/region/footer.html.twig';
            default:
                return '@MapbenderCore/Template/region/generic.html.twig';
        }
    }

    public function getRegionTemplateVars(\Mapbender\CoreBundle\Entity\Application $application, $regionName)
    {
        $allRegionProps = $application->getNamedRegionProperties();
        if (!empty($allRegionProps[$regionName])) {
            $regionProps = $allRegionProps[$regionName]->getProperties() ?: array();
        } else {
            $regionProps = array();
        }
        return array(
            'region_props' => $regionProps,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLateAssets($type)
    {
        switch ($type) {
            case 'js':
            case 'css':
            case 'trans':
                return array();
            default:
                throw new \InvalidArgumentException("Unsupported late asset type " . print_r($type, true));
        }
    }

    public function getTemplateVars(\Mapbender\CoreBundle\Entity\Application $application)
    {
        return array(
            'region_props' => $application->getNamedRegionProperties(),
        );
    }

    /**
     * Get the available regions properties.
     *
     * @return array
     */
    public static function getRegionsProperties()
    {
        return array();
    }

    /**
     * @return string TWIG template path
     */
    abstract public function getTwigTemplate();

    final public static function getValidOverlayAnchors()
    {
        return array(
            self::OVERLAY_ANCHOR_LEFT_TOP,
            self::OVERLAY_ANCHOR_RIGHT_TOP,
            self::OVERLAY_ANCHOR_LEFT_BOTTOM,
            self::OVERLAY_ANCHOR_RIGHT_BOTTOM,
        );
    }

    /**
     * @param \Mapbender\CoreBundle\Entity\Application $application
     * @param string $regionName
     * @return array
     */
    protected static function extractRegionProperties(\Mapbender\CoreBundle\Entity\Application $application, $regionName)
    {
        $propsObject = $application->getPropertiesFromRegion($regionName) ?: new RegionProperties();
        return $propsObject->getProperties() ?: array();
    }

    public function getBodyClass(\Mapbender\CoreBundle\Entity\Application $application)
    {
        return '';
    }
}

