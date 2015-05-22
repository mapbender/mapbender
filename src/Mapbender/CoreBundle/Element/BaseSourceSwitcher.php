<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

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
    public static function getClassTags()
    {
        return array(
            "mb.core.basesourceswitcher.tag.base",
            "mb.core.basesourceswitcher.tag.source",
            "mb.core.basesourceswitcher.tag.switcher"
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => "BaseSourceSwitcher",
            'target' => null,
            'display'  => null,
            'instancesets' => array()
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
    public static function listAssets()
    {
        return array(
            'js' => array('mapbender.element.basesourceswitcher.js'),
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/element/basesourceswitcher.scss')
        );
    }
    
    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = $confHelp = parent::getConfiguration();
        if (isset($configuration['instancesets'])) {
            unset($configuration['instancesets']);
        }
        $configuration['groups'] = array();
        foreach ($confHelp['instancesets'] as $instanceset) {
            if (isset($instanceset['group']) && $instanceset['group'] !== '') {
                if (!isset($configuration['groups'][$instanceset['group']])) {
                    $configuration['groups'][$instanceset['group']] = array();
                }
                $configuration['groups'][$instanceset['group']][] = array(
                    'title' => $instanceset['title'],
                    'sources' => $instanceset['instances']
                );
            } else {
                $configuration['groups'][$instanceset['title']] = array(
                    'title' => $instanceset['title'],
                    'sources' => $instanceset['instances']
                );
            }
        }
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
            'MapbenderCoreBundle:Element:basesourceswitcher.html.twig',
            array(
                'id' => $this->getId(),
                "title" => $this->getTitle(),
                'configuration' => $this->getConfiguration()
            )
        );
    }

    /**
     * Changes a element entity configuration while exporting.
     *
     * @param array $formConfiguration element entity configuration
     * @param array $entityConfiguration element entity configuration
     * @return array a configuration
     */
    public function normalizeConfiguration(array $formConfiguration, array $entityConfiguration = array())
    {
        return $formConfiguration;
    }

    /**
     * Changes a element entity configuration while importing.
     *
     * @param array $configuration element entity configuration
     * @param array $idMapper array with ids before denormalize and after denormalize.
     * @return array a configuration
     */
    public function denormalizeConfiguration(array $configuration, array $idMapper = array())
    {
        $instClasses = array();
        foreach ($idMapper as $class => $content) {
            if ($this->findSuperClass($class, 'Mapbender\CoreBundle\Entity\SourceInstance')) {
                $instClasses[] = $class;
            }
        }
        foreach ($configuration['instancesets'] as $key => &$instanceset) {
            foreach ($instanceset['instances'] as &$instance) {
                if ($instance && count($instClasses)) {
                    foreach ($instClasses as $instClass) {
                        if (isset($idMapper[$instClass]['map'][intval($instance)])) {
                            $instance = $idMapper[$instClass]['map'][intval($instance)];
                        }
                    }
                }
            }
        }
        return $configuration;
    }

    private function findSuperClass($classIs, $classToFind)
    {
        if ($classIs === $classToFind) {
            return true;
        } elseif ($super = get_parent_class($classIs)) {
            return $this->findSuperClass($super, $classToFind);
        } else {
            return false;
        }
    }
}
