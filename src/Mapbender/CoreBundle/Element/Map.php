<?php

namespace Mapbender\CoreBundle\Element;

use Doctrine\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Entity\Layerset;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ImportAwareInterface;
use Mapbender\Component\Element\MainMapElementInterface;
use Mapbender\Component\Element\StaticView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\ElementBase\ValidatableConfigurationInterface;
use Mapbender\CoreBundle\Component\ElementBase\ValidationFailedException;
use Mapbender\CoreBundle\Element\Type\MapAdminType;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\SRS;
use Mapbender\ManagerBundle\Component\Mapper;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Map element.
 *
 * @author Christian Wygoda
 */
class Map extends AbstractElementService
    implements MainMapElementInterface, ConfigMigrationInterface, ImportAwareInterface, ValidatableConfigurationInterface
{

    const MINIMUM_TILE_SIZE = 128;

    protected ObjectRepository $srsRepository;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->srsRepository = $managerRegistry->getRepository(SRS::class);
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.map.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.map.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        /* "standardized rendering pixel size" for WMTS 0.28 mm × 0.28 mm -> DPI for WMTS: 90.714285714 */
        return [
            'layersets' => [],
            'srs' => 'EPSG:4326',
            'otherSrs' => ["EPSG:25832","EPSG:25833","EPSG:3857","EPSG:31466", "EPSG:31467"],
            'base_dpi' => 96,
            'tileSize' => 512,
            'extent_max' => [0, 40, 20, 60.8],
            'extent_start' => [7.03, 50.71, 7.17, 50.76],
            "scales" => [7500000,5000000,1000000,500000,100000,50000,25000,10000,7500,5000,2500,1000],
            'fixedZoomSteps' => false,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbMap';
    }

    public function getView(Element $element): StaticView
    {
        $view = new StaticView('');
        $view->attributes['class'] = 'mb-element-map';

        return $view;
    }

    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbMap.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/map.scss',
            ]
        ];
    }

    /**
     * Returns a list of all configured SRSes, producing an array with 'name' and 'title' for each
     * @param Element $element
     * @return string[][]
     */
    protected function buildSrsConfigs(Element $element): array
    {
        $customTitles = [];
        $configuration = $element->getConfiguration();
        $mainSrsParts = preg_split("/\s*\|\s*/", trim($configuration["srs"]));
        $defaultSrsName = $mainSrsParts[0];
        $configuration['srs'] = $defaultSrsName;
        if (!empty($mainSrsParts[1])) {
            $customTitles[$mainSrsParts[0]] = $mainSrsParts[1];
        }
        $srsNames = [$defaultSrsName];
        if (!empty($configuration['otherSrs'])) {
            $otherSrsConfigs = $configuration['otherSrs'];
            if (\is_string($otherSrsConfigs)) {
                $otherSrsConfigs = preg_split('/\s*,\s*/', trim($otherSrsConfigs));
            }

            foreach ($otherSrsConfigs as $srs) {
                $otherSrsParts = preg_split("/\s*\|\s*/", trim((string) $srs));
                if ($otherSrsParts[0] !== $defaultSrsName) {
                    $srsNames[] = $otherSrsParts[0];
                    if (!empty($otherSrsParts[1])) {
                        $customTitles[$otherSrsParts[0]] = $otherSrsParts[1];
                    }
                }
            }
        }
        $defs = $this->getSrsDefinitions($srsNames);
        foreach ($defs as $i => $def) {
            if (!empty($customTitles[$def['name']])) {
                $defs[$i]['title'] = $customTitles[$def['name']];
            }
        }
        return [
            'srs' => $defaultSrsName,
            'srsDefs' => $defs,
        ];
    }

    /**
     * @param Element $element
     * @return array
     */
    public function getClientConfiguration(Element $element): array
    {
        // Remove nulls, readd defaults (only for yaml-based apps, for db-based apps validation is done using form constraints)
        $conf = \array_filter($element->getConfiguration(), fn($v): bool => $v !== null);
        $conf += static::getDefaultConfiguration();
        $conf['tileSize'] = \intval(max(self::MINIMUM_TILE_SIZE, $conf['tileSize']));
        $conf = $this->buildSrsConfigs($element) + $conf;
        return $conf;
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return MapAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/map.html.twig';
    }

    /**
     * Returns proj4js srs definitions from srs names
     * @param string[] $names
     * @return string[][]
     */
    protected function getSrsDefinitions(array $names): array
    {
        /** @var SRS[] $srses */
        $srses = $this->srsRepository->findBy([
            'name' => $names,
        ]);
        $defs = [];
        foreach ($srses as $srs) {
            $defs[] = [
                'name' => $srs->getName(),
                'title' => $srs->getTitle(),
                'definition' => $srs->getDefinition(),
            ];
        }
        return $defs;
    }

    public function onImport(Element $element, Mapper $mapper): void
    {
        $configuration = $element->getConfiguration();
        if (!empty($configuration['layersets'])) {
            $newIds = [];
            foreach ($configuration['layersets'] as $oldId) {
                $newIds[] = $mapper->getIdentFromMapper(Layerset::class, $oldId);
            }
            $configuration['layersets'] = $newIds;
            $element->setConfiguration($configuration);
        }
    }

    public static function updateEntityConfig(Element $entity): void
    {
        $config = $entity->getConfiguration();
        if (isset($config['layerset']) && !isset($config['layersets'])) {
            // legacy db config, promote to array-form 'layersets'
            $config['layersets'] = (array)$config['layerset'];
        }
        unset($config['layerset']);

        if (!empty($config['extents']['start'])) {
            $config['extent_start'] = $config['extents']['start'];
        }
        if (!empty($config['extents']['max'])) {
            $config['extent_max'] = $config['extents']['max'];
        }
        unset($config['extents']);

        $defaults = static::getDefaultConfiguration();
        $config += [
            'otherSrs' => $defaults['otherSrs'],
            'scales' => $defaults['scales'],
            'tileSize' => $defaults['tileSize'],
        ];

        if (is_string($config['otherSrs'])) {
            $config['otherSrs'] = explode(',', $config['otherSrs']);
        }
        if (is_string($config['scales'])) {
            $config['scales'] = explode(',', $config['scales']);
        }
        $config['scales'] = array_values(array_map('intval', $config['scales']));

        $entity->setConfiguration($config);
    }

    public static function validate(array $configuration, ?FormInterface $form, TranslatorInterface $translator): void
    {
        // check that max > min for all cases
        foreach (['extent_start', 'extent_max'] as $key) {
            $extent = $configuration[$key];
            foreach ([0, 1] as $index) {
                if ($extent[$index] >= $extent[$index + 2]) {
                    $msg = $translator->trans('mb.core.map.error.extent_wrong');
                    $msg = str_replace("%dim", $index === 0 ? 'x' : 'y', $msg);
                    if ($form !== null) {
                        $form->get('configuration')->get($key)->get($index)->addError(new FormError($msg));
                        $form->get('configuration')->get($key)->get($index + 2)->addError(new FormError(""));
                    } else {
                        throw new ValidationFailedException($msg);
                    }
                }
            }

        }
    }
}
