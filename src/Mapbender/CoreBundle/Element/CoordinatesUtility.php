<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\CoordinatesUtilityAdminType;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\SRS;

class CoordinatesUtility extends AbstractElementService implements ConfigMigrationInterface
{
    public function __construct(protected ManagerRegistry $doctrineRegistry)
    {
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.coordinatesutility.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.coordinatesutility.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbCoordinatesUtility.js',
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
    public static function getDefaultConfiguration(): array
    {
        return [
            'srsList' => [],
            'addMapSrsList' => true,
            'zoomlevel' => 6,
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbCoordinatesUtility';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return CoordinatesUtilityAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/coordinatesutility.html.twig';
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/coordinatesutility.html.twig');
        $view->attributes['class'] = 'mb-element-coordinatesutility';
        $view->attributes['data-title'] = $element->getTitle() ?: static::getClassTitle();
        $view->variables['dialogMode'] = !\preg_match('#sidepane|mobilepane#i', (string) $element->getRegion());
        return $view;
    }

    public function getClientConfiguration(Element $element)
    {
        $conf = $element->getConfiguration() ?: [];

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
    protected function normalizeSrsList(array $srsList): array
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
            $srsList[$k] = [
                'name' => $name,
                'title' => trim((string) $title) ?: null,
            ];
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
    public function getSrsDefinitionsFromDatabase($srsList): array
    {
        $srsNames = array_map(fn(array $srs) => $srs['name'], $srsList);
        /** @var SRS[] $entities */
        $entities = $this->doctrineRegistry->getRepository(SRS::class)->findBy([
            'name' => $srsNames,
        ]);
        $entityMap = [];
        foreach ($entities as $srs) {
            $entityMap[$srs->getName()] = $srs;
        }
        return $entityMap;
    }

    public static function updateEntityConfig(Element $entity): void
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

    public static function getDefaultIcon(): string
    {
        return 'iconCoordinates';
    }
}
