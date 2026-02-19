<?php

namespace Mapbender\CoreBundle\Element;

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
    public static function getClassTitle()
    {
        return "mb.core.basesourceswitcher.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.basesourceswitcher.class.Description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => static::getClassTitle(),
            'instancesets' => array(),
            'anchor' => 'right-bottom',
            'element_icon' => self::getDefaultIcon(),
        );
    }

    public static function getDefaultIcon()
    {
        return 'iconMap';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbBaseSourceSwitcher';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseSourceSwitcherAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderCore/ElementAdmin/basesourceswitcher.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/elements/MbBaseSourceSwitcher.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/basesourceswitcher.scss',
            ),
            'trans' => array(
                'mb.core.basesourceswitcher.error.*',
            ),
        );
    }

    protected function mergeGroups(Element $element)
    {
        $rawConf = $element->getConfiguration();
        $itemsOut = array();
        if (empty($rawConf['instancesets']) || !is_array($rawConf['instancesets'])) {
            // @todo: throw config error if wrong type
            $itemConfigs = array();
        } else {
            $itemConfigs = $rawConf['instancesets'];
        }
        foreach ($itemConfigs as $itemIn) {
            $itemOut = array(
                'type'    => 'item',
                'title'   => $itemIn['title'],
                'sources' => $itemIn['instances']
            );
            $isGroup = !empty($itemIn['group']);
            if ($isGroup) {
                $groupName = $itemIn['group'];
                if (empty($itemsOut[$groupName])) {
                    $itemsOut[$groupName] = array(
                        'type' => 'group',
                        'title' => $groupName,
                        'items' => array(),
                    );
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

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderCore/Element/basesourceswitcher.html.twig');
        $view->attributes['class'] = 'mb-element-basesourceswitcher';
        if (\preg_match('#toolbar|footer#i', $element->getRegion())) {
            $view->attributes['title'] = $element->getConfiguration()['tooltip'] ?: $element->getTitle();
        }

        $view->variables = array(
            'configuration' => array(
                'groups' => $this->mergeGroups($element),
            ),
        );
        return $view;
    }


    public function onImport(Element $element, Mapper $mapper)
    {
        $configuration = $element->getConfiguration();
        foreach ($configuration['instancesets'] as $setId => $instanceset) {
            foreach ($instanceset['instances'] as $k => $instanceId) {
                if ($instanceId) {
                    $newId = $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\SourceInstance', $instanceId, true);
                    $configuration['instancesets'][$setId]['instances'][$k] = $newId;
                }
            }
        }
        $element->setConfiguration($configuration);
    }
}
