<?php
namespace Mapbender\ManagerBundle\Component;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Component\Exchange\ExportDataPool;

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
        $entityBuffer = new ExportDataPool();
        gc_enable();
        $normalizer = new ExchangeNormalizer($this->em);
        $time = array(
            'start' => microtime(true)
        );
        foreach ($this->getApplicationSourceInstances($application) as $source) {
            $normalizer->handleValue($entityBuffer, $source);
            gc_collect_cycles();
        }
        $time['sources'] = microtime(true);
        $time['sources'] = $time['sources'] . '/' . ($time['sources'] - $time['start']);

        $normalizer->handleValue($entityBuffer, $application);
        gc_collect_cycles();
        $time['end'] = microtime(true);
        $time['total'] = $time['end'] - $time['start'];
        gc_collect_cycles();
        $export = $entityBuffer->getAllGroupedByClassName();

        $export['time'] = $time;
        return $export;
    }

    /**
     * Get current user allowed application sources
     *
     * @param Application $app
     * @return SourceInstance[]
     */
    protected function getApplicationSourceInstances(Application $app)
    {
        $instanceIds = array();
        $instances = array();
        foreach ($app->getLayersets() as $layerSet) {
            foreach ($layerSet->getInstances() as $instance) {
                $instanceId = $instance->getId();
                if (!in_array($instanceId, $instanceIds)) {
                    $instanceIds[] = $instanceId;
                    $instances[] = $instance;
                }
            }
        }
        return $instances;
    }
}
