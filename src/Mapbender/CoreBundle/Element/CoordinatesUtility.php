<?php

namespace Mapbender\CoreBundle\Element;

use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\SRS;

class CoordinatesUtility extends AbstractElementService implements ConfigMigrationInterface
{
    /** @var ManagerRegistry */
    protected $doctrineRegistry;

    public function __construct(ManagerRegistry $doctrineRegistry)
    {
        $this->doctrineRegistry = $doctrineRegistry;
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.coordinatesutility.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.coordinatesutility.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/element/mapbender.element.coordinatesutility.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/coordinatesutility.scss',
            ],
            'trans' => [
                'mb.core.coordinatesutility.widget.*',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return [
            'srsList' => array(),
            'addMapSrsList' => true,
            'zoomlevel' => 6,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbCoordinatesUtility';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\CoordinatesUtilityAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:coordinatesutility.html.twig';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:coordinatesutility.html.twig');
        $view->attributes['class'] = 'mb-element-coordinatesutility';
        $view->attributes['data-title'] = $element->getTitle() ?: $this->getClassTitle();
        return $view;
    }

    public function getClientConfiguration(Element $element)
    {
        $conf = $element->getConfiguration() ?: array();

        if (!empty($conf['srsList'])) {
            $conf['srsList'] = $this->addSrsDefinitions($conf['srsList']);
        }
        return $conf;
    }

    /**
     * @param $srsList
     * @return mixed
     */
    public function addSrsDefinitions($srsList)
    {
        $srsList = $this->normalizeSrsList($srsList);
        $srsWithDefinitions = $this->getSrsDefinitionsFromDatabase($srsList);

        foreach ($srsList as $key => $srsSpec) {
            $srsName = $srsSpec['name'];

            if (isset($srsWithDefinitions[$srsName])) {
                $srs = $srsWithDefinitions[$srsName];
                $srsList[$key]['definition'] = $srs->getDefinition();
                if (empty($srsList[$key]['title'])) {
                    $srsList[$key]['title'] = $srs->getTitle() ?: $srs->getName();
                }
            } elseif (empty($srsList[$key]['title'])) {
                $srsList[$key]['title'] = $srsList[$key]['name'];
            }
        }

        return $srsList;
    }

    /**
     * @param mixed[] $srsList strings or arrays
     * @return mixed[][]
     */
    protected function normalizeSrsList($srsList)
    {
        // Tolerate both arrays + scalars
        /** @see Type\CoordinatesUtilityAdminType::reverseTransform */
        foreach ($srsList as $k => $srsSpec) {
            if (\is_string($srsSpec)) {
                $parts = explode('|', $srsSpec, 2);
                $name = trim($parts[0]);
                $title = (count($parts) > 1) ? $parts[1] : null;
            } else {
                $name = $srsSpec['name'];
                $title = !empty($srsSpec['title']) ? $srsSpec['title'] : null;
            }
            $srsList[$k] = array(
                'name' => $name,
                'title' => trim($title) ?: null,
            );
        }
        foreach ($srsList as $k => $srsSpec) {
            if (empty($srsSpec['name'])) {
                unset($srsList[$k]);
            }
        }
        return \array_values($srsList);
    }

    /**
     * @param $srsList
     * @return SRS[] keyed on name
     */
    public function getSrsDefinitionsFromDatabase($srsList)
    {
        $srsNames = array_map(function($srs) {
            return $srs['name'];
        }, $srsList);
        /** @var SRS[] $entities */
        $entities = $this->doctrineRegistry->getRepository(SRS::class)->findBy(array(
            'name' => $srsNames,
        ));
        $entityMap = array();
        foreach ($entities as $srs) {
            $entityMap[$srs->getName()] = $srs;
        }
        return $entityMap;
    }

    public static function updateEntityConfig(Element $entity)
    {
        $conf = $entity->getConfiguration();
        // Coords utility doesn't have an autoOpen backend option, and doesn't support it in the frontend
        // However, some legacy / cloned / YAML-based etc Applications may have a value there that will
        // royally confuse controlling buttons. Just make sure it's never there.
        unset($conf['autoOpen']);
        // Amend zoomlevel
        // NOTE: '0' is a valid zoomlevel (avoid !empty check)
        if (!\array_key_exists('zoomlevel', $conf) || !\is_numeric($conf['zoomlevel'])) {
            $conf['zoomlevel'] = static::getDefaultConfiguration()['zoomlevel'];
        }

        $entity->setConfiguration($conf);
    }
}
