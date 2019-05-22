<?php
namespace Mapbender\ManagerBundle\Component;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
class ExportHandler extends ExchangeHandler
{
    /**
     * @param Application $application
     * @return array
     */
    public function exportApplication(Application $application)
    {
        gc_enable();
        $normalizer = new ExchangeNormalizer($this->em);
        $time = array(
            'start' => microtime(true)
        );
        foreach ($this->getApplicationSources($application) as $source) {
            $normalizer->handleValue($source);
            gc_collect_cycles();
        }
        $time['sources'] = microtime(true);
        $time['sources'] = $time['sources'] . '/' . ($time['sources'] - $time['start']);

        $normalizer->handleValue($application);
        gc_collect_cycles();
        $time['end'] = microtime(true);
        $time['total'] = $time['end'] - $time['start'];
        gc_collect_cycles();
        $export = $normalizer->getExport();
        $export['time'] = $time;
        return $export;
    }

    /**
     * Get current user allowed application sources
     *
     * @param Application $app
     * @return Source[]
     */
    protected function getApplicationSources(Application $app)
    {
        $sourceIds = array();
        $sources = array();
        foreach ($app->getLayersets() as $layerSet) {
            foreach ($layerSet->getInstances() as $instance) {
                $source = $instance->getSource();
                $sourceId = $source->getId();
                if (!in_array($sourceId, $sourceIds)) {
                    $sourceIds[] = $sourceId;
                    $sources[] = $source;
                }
            }
        }
        return $sources;
    }
}
