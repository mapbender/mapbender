<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\BaseSourceSwitcherAdminType;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ImportAwareInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 * @author Paul Schmidt
 */
class BaseSourceSwitcher extends AbstractElementService
    implements FloatableElement, ImportAwareInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.basesourceswitcher.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.basesourceswitcher.class.Description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'tooltip' => static::getClassTitle(),
            'instancesets' => [],
            'anchor' => 'right-bottom',
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    public static function getDefaultIcon(): string
    {
        return 'iconMap';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbBaseSourceSwitcher';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return BaseSourceSwitcherAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/basesourceswitcher.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbBaseSourceSwitcher.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/basesourceswitcher.scss',
            ],
            'trans' => [
                'mb.core.basesourceswitcher.error.*',
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    protected function mergeGroups(Element $element): array
    {
        $rawConf = $element->getConfiguration();
        $itemsOut = [];
        if (empty($rawConf['instancesets']) || !is_array($rawConf['instancesets'])) {
            throw new \RuntimeException("[BaseSourceSwitcher] Invalid configuration: 'instancesets' must be an array");
        }
        $itemConfigs = $rawConf['instancesets'];
        foreach ($itemConfigs as $itemIn) {
            $itemOut = [
                'type'    => 'item',
                'title'   => $itemIn['title'],
                'sources' => $itemIn['instances']
            ];
            $isGroup = !empty($itemIn['group']);
            if ($isGroup) {
                $groupName = $itemIn['group'];
                if (empty($itemsOut[$groupName])) {
                    $itemsOut[$groupName] = [
                        'type' => 'group',
                        'title' => $groupName,
                        'items' => [],
                    ];
                }
                $itemsOut[$groupName]['items'][] = $itemOut;
            } else {
                $itemsOut[$itemIn['title']] = $itemOut;
            }
        }
        foreach ($itemsOut as &$firstGroup) {
            $firstGroup['active'] = true;
            if ($firstGroup['type'] == 'group' && $firstGroup['items']) {
                $firstGroup['items'][0]['active'] = true;
            }
            break;
        }
        return $itemsOut;
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/basesourceswitcher.html.twig');
        $view->attributes['class'] = 'mb-element-basesourceswitcher';
        if (\preg_match('#toolbar|footer#i', (string) $element->getRegion())) {
            $view->attributes['title'] = $element->getConfiguration()['tooltip'] ?: $element->getTitle();
        }

        $view->variables = [
            'configuration' => [
                'groups' => $this->mergeGroups($element),
            ],
        ];
        return $view;
    }


    public function onImport(Element $element, Mapper $mapper): void
    {
        $configuration = $element->getConfiguration();
        foreach ($configuration['instancesets'] as $setId => $instanceset) {
            foreach ($instanceset['instances'] as $k => $instanceId) {
                if ($instanceId) {
                    $newId = $mapper->getIdentFromMapper(SourceInstance::class, $instanceId, true);
                    $configuration['instancesets'][$setId]['instances'][$k] = $newId;
                }
            }
        }
        $element->setConfiguration($configuration);
    }
}
