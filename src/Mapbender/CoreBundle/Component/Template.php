<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateAssetDependencyInterface;
use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateInterface;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Utils\ArrayUtil;

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

    /**
     * {@inheritdoc}
     */
    public function getAssets($type)
    {
        switch ($type) {
            case 'js':
            case 'css':
            case 'trans':
                return ['mb.error.*'];
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }

    public function getRegionTemplate(Application $application, $regionName)
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

    public static function getRegionTitle($regionName)
    {
        switch ($regionName) {
            default:
                return \ucfirst($regionName);
            case 'sidepane':
                return 'mb.template.region.sidepane';
            case 'toolbar':
                return 'mb.template.region.toolbar';
            case 'footer':
                return 'mb.template.region.footer';
            case 'content':
                return 'mb.template.region.content';
        }
    }

    public function getRegionTemplateVars(Application $application, $regionName)
    {
        switch ($regionName) {
            default:
                return array();
            case 'toolbar':
            case 'footer':
                return array_replace(array(
                    'alignment_class' => $this->getToolbarAlignmentClass($application, $regionName),
                ));
        }
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @return string[]
     */
    public function getRegionClasses(Application $application, $regionName)
    {
        $classes = array();
        switch ($regionName) {
            case 'toolbar':
                $classes[] = 'top';
                break;
            case 'footer':
                $classes[] = 'bottom';
                break;
            default:
                break;
        }
        return $classes;
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

    public function getTemplateVars(Application $application)
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
     * @param string $regionName
     * @return string|null
     */
    public static function getRegionSettingsFormType($regionName)
    {
        return null;
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
     * @param Application $application
     * @param string $regionName
     * @return array
     */
    protected static function extractRegionProperties(Application $application, $regionName)
    {
        $propsObject = $application->getPropertiesFromRegion($regionName) ?: new RegionProperties();
        return $propsObject->getProperties() ?: array();
    }

    public function getBodyClass(Application $application)
    {
        return '';
    }

    public static function getRegionPropertiesDefaults($regionName)
    {
        $definitions = ArrayUtil::getDefault(static::getRegionsProperties(), $regionName) ?: array();
        $defaults = array();
        foreach ($definitions as $name => $value) {
            if (\is_array($value)) {
                $defaults += $value;
            } else {
                $defaults[$name] = $value;
            }
        }
        return $defaults;
    }

    public static function getToolbarAlignmentClass(Application $application, $regionName)
    {
        $regionSettings = static::extractRegionProperties($application, $regionName) + static::getRegionPropertiesDefaults($regionName);
        $setting = ArrayUtil::getDefault($regionSettings, 'item_alignment');
        switch ($setting) {
            default:
            case 'left':
                return 'itemsLeft';
            case 'right':
                return 'itemsRight';
            case 'center':
                return 'itemsCenter';
        }
    }
}
