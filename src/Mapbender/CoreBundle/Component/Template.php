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
        return match ($type) {
            'js', 'css', 'trans' => ['mb.error.*'],
            default => throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true)),
        };
    }

    public function getRegionTemplate(Application $application, $regionName)
    {
        return match ($regionName) {
            'sidepane' => '@MapbenderCore/Template/region/sidepane.html.twig',
            'toolbar' => '@MapbenderCore/Template/region/toolbar.html.twig',
            'footer' => '@MapbenderCore/Template/region/footer.html.twig',
            default => '@MapbenderCore/Template/region/generic.html.twig',
        };
    }

    public static function getRegionTitle($regionName)
    {
        return match ($regionName) {
            'sidepane' => 'mb.template.region.sidepane',
            'toolbar' => 'mb.template.region.toolbar',
            'footer' => 'mb.template.region.footer',
            'content' => 'mb.template.region.content',
            default => \ucfirst((string) $regionName),
        };
    }

    public function getRegionTemplateVars(Application $application, $regionName)
    {
        return match ($regionName) {
            'toolbar', 'footer' => array_replace([
                'alignment_class' => static::getToolbarAlignmentClass($application, $regionName),
            ]),
            default => [],
        };
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @return string[]
     */
    public function getRegionClasses(Application $application, $regionName)
    {
        $classes = [];
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
        return match ($type) {
            'js', 'css', 'trans' => [],
            default => throw new \InvalidArgumentException("Unsupported late asset type " . print_r($type, true)),
        };
    }

    public function getTemplateVars(Application $application)
    {
        return [
            'region_props' => $application->getNamedRegionProperties(),
        ];
    }

    /**
     * Get the available regions properties.
     *
     * @return array
     */
    public static function getRegionsProperties()
    {
        return [];
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
        return [
            self::OVERLAY_ANCHOR_LEFT_TOP,
            self::OVERLAY_ANCHOR_RIGHT_TOP,
            self::OVERLAY_ANCHOR_LEFT_BOTTOM,
            self::OVERLAY_ANCHOR_RIGHT_BOTTOM,
        ];
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @return array
     */
    protected static function extractRegionProperties(Application $application, $regionName)
    {
        $propsObject = $application->getPropertiesFromRegion($regionName) ?: new RegionProperties();
        return $propsObject->getProperties() ?: [];
    }

    public function getBodyClass(Application $application)
    {
        return '';
    }

    public static function getRegionPropertiesDefaults($regionName)
    {
        $definitions = ArrayUtil::getDefault(static::getRegionsProperties(), $regionName) ?: [];
        $defaults = [];
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
