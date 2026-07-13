<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\OverviewAdminType;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ImportAwareInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\ElementBase\FloatingElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class Overview extends AbstractElementService
    implements FloatingElement, ImportAwareInterface, ConfigMigrationInterface
{

    const VISIBILITY_CLOSED_INITIALLY = 'closed';
    const VISIBILITY_OPEN_INITIALLY = 'open';
    const VISIBILITY_OPEN_PERMANENT = 'open-permanent';

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.overview.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.overview.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'layerset' => null,
            'width' => 200,
            'height' => 100,
            'anchor' => 'right-bottom',
            'visibility' => self::VISIBILITY_OPEN_INITIALLY,
            'fixed' => false,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbOverview';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return OverviewAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbOverview.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/overview.scss',
            ],
            'trans' => [
                'mb.core.overview.nolayer',
            ],
        ];
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/overview.html.twig');
        $view->attributes['class'] = 'mb-element-overview';
        $config = $element->getConfiguration();
        switch ($config['visibility']) {
            case self::VISIBILITY_CLOSED_INITIALLY:
                $view->attributes['class'] .= ' closed';
                $view->variables['show_toggle'] = true;
                break;
            default:
            case self::VISIBILITY_OPEN_INITIALLY:
                $view->variables['show_toggle'] = true;
                break;
            case self::VISIBILITY_OPEN_PERMANENT:
                $view->variables['show_toggle'] = false;
                break;
        }
        $view->variables += [
            'closed' => $config['visibility'] == self::VISIBILITY_CLOSED_INITIALLY,
            'width' => $config['width'],
            'height' => $config['height'],
        ];
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/overview.html.twig';
    }

    public function onImport(Element $element, Mapper $mapper): void
    {
        $configuration = $element->getConfiguration();
        if (isset($configuration['layerset'])) {
            $configuration['layerset'] = $mapper->getIdentFromMapper(
                Layerset::class,
                $configuration['layerset'],
                true
            );
            $element->setConfiguration($configuration);
        }
    }

    public static function updateEntityConfig(Element $entity): void
    {
        $config = $entity->getConfiguration() ?: [];
        if (\array_key_exists('maximized', $config)) {
            $config += [
                'visibility' => ($config['maximized'] ? self::VISIBILITY_OPEN_INITIALLY : self::VISIBILITY_CLOSED_INITIALLY),
            ];
            unset($config['maximized']);
            $entity->setConfiguration($config);
        }
    }
}
