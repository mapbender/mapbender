<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class BaseSourceSwitcher extends Element
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
            'tooltip' => "BaseSourceSwitcher",
            'target' => null,
            'instancesets' => array(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbBaseSourceSwitcher';
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
        return 'MapbenderCoreBundle:ElementAdmin:basesourceswitcher.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.basesourceswitcher.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/basesourceswitcher.scss',
            ),
            'trans' => array(
                'mb.core.basesourceswitcher.error.*',
            ),
        );
    }

    protected function mergeGroups(Entity\Element $element)
    {
        $rawConf = $element->getConfiguration();
        $itemsOut = array();
        if (empty($rawConf['instancesets']) || !is_array($rawConf['instancesets'])) {
            // @todo: throw config error if wrong type, complain about empty array
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

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:basesourceswitcher.html.twig';
    }

    public function getFrontendTemplateVars()
    {
        $rawConf = $this->entity->getConfiguration();
        return array(
            'id' => $this->entity->getId(),
            'title' => $this->entity->getTitle(),
            'configuration' => $rawConf + array(
                'groups' => $this->mergeGroups($this->entity),
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $template = $this->getFrontendTemplatePath();
        $vars = $this->getFrontendTemplateVars();
        return $this->container->get('templating')->render($template, $vars);
    }

    /**
     * @inheritdoc
     */
    public function denormalizeConfiguration(array $configuration, Mapper $mapper)
    {
        foreach ($configuration['instancesets'] as $key => &$instanceset) {
            foreach ($instanceset['instances'] as &$instance) {
                if ($instance) {
                    $instance =
                        $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\SourceInstance', $instance, true);
                }
            }
        }
        return $configuration;
    }
}
