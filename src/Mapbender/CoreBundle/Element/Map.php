<?php

namespace Mapbender\CoreBundle\Element;

use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ImportAwareInterface;
use Mapbender\Component\Element\MainMapElementInterface;
use Mapbender\Component\Element\StaticView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\ElementBase\ValidatableConfigurationInterface;
use Mapbender\CoreBundle\Component\ElementBase\ValidationFailedException;
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

    /** @var \Doctrine\Persistence\ObjectRepository */
    protected $srsRepository;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->srsRepository = $managerRegistry->getRepository(SRS::class);
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.map.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.map.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        /* "standardized rendering pixel size" for WMTS 0.28 mm × 0.28 mm -> DPI for WMTS: 90.714285714 */
        return array(
            'layersets' => array(),
            'srs' => 'EPSG:4326',
            'otherSrs' => array("EPSG:25832","EPSG:25833","EPSG:3857","EPSG:31466", "EPSG:31467"),
            'base_dpi' => 96,
            'tileSize' => 512,
            'extent_max' => array(0, 40, 20, 60.8),
            'extent_start' => array(7.03, 50.71, 7.17, 50.76),
            "scales" => array(7500000,5000000,1000000,500000,100000,50000,25000,10000,7500,5000,2500,1000),
            'fixedZoomSteps' => false,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbMap';
    }

    public function getView(Element $element)
    {
        $view = new StaticView('');
        $view->attributes['class'] = 'mb-element-map';

        return $view;
    }

    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/elements/MbMap.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/map.scss',
            )
        );
    }

    /**
     * Returns a list of all configured SRSes, producing an array with 'name' and 'title' for each
     * @param Element $element
     * @return string[][]
     */
    protected function buildSrsConfigs(Element $element)
    {
        $customTitles = array();
        $configuration = $element->getConfiguration();
        $mainSrsParts = preg_split("/\s*\|\s*/", trim($configuration["srs"]));
        $defaultSrsName = $mainSrsParts[0];
        $configuration['srs'] = $defaultSrsName;
        if (!empty($mainSrsParts[1])) {
            $customTitles[$mainSrsParts[0]] = $mainSrsParts[1];
        }
        $srsNames = array($defaultSrsName);
        if (!empty($configuration['otherSrs'])) {
            $otherSrsConfigs = $configuration['otherSrs'];
            if (\is_string($otherSrsConfigs)) {
                $otherSrsConfigs = preg_split('/\s*,\s*/', trim($otherSrsConfigs));
            }

            foreach ($otherSrsConfigs as $srs) {
                $otherSrsParts = preg_split("/\s*\|\s*/", trim($srs));
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
        return array(
            'srs' => $defaultSrsName,
            'srsDefs' => $defs,
        );
    }

    /**
     * @param Element $element
     * @return array
     */
    public function getClientConfiguration(Element $element)
    {
        // Remove nulls, readd defaults
        // @todo: prevent saving invalid empty values via form constraints
        $conf = \array_filter($element->getConfiguration(), function ($v) {
            return $v !== null;
        });
        $conf += static::getDefaultConfiguration();
        $conf['tileSize'] = \intval(max(self::MINIMUM_TILE_SIZE, $conf['tileSize']));
        $conf = $this->buildSrsConfigs($element) + $conf;
        return $conf;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\MapAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderManager/Element/map.html.twig';
    }

    /**
     * Returns proj4js srs definitions from srs names
     * @param string[] $names
     * @return string[][]
     */
    protected function getSrsDefinitions(array $names)
    {
        /** @var SRS[] $srses */
        $srses = $this->srsRepository->findBy(array(
            'name' => $names,
        ));
        $defs = array();
        foreach ($srses as $srs) {
            $defs[] = array(
                'name' => $srs->getName(),
                'title' => $srs->getTitle(),
                'definition' => $srs->getDefinition(),
            );
        }
        return $defs;
    }

    public function onImport(Element $element, Mapper $mapper)
    {
        $configuration = $element->getConfiguration();
        if (!empty($configuration['layersets'])) {
            $newIds = array();
            foreach ($configuration['layersets'] as $oldId) {
                $newIds[] = $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\Layerset', $oldId);
            }
            $configuration['layersets'] = $newIds;
            $element->setConfiguration($configuration);
        }
    }

    public static function updateEntityConfig(Element $entity)
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
        $config += array(
            'otherSrs' => $defaults['otherSrs'],
            'scales' => $defaults['scales'],
            'tileSize' => $defaults['tileSize'],
        );

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
