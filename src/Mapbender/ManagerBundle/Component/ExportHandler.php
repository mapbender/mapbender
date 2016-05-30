<?php
namespace Mapbender\ManagerBundle\Component;

use Mapbender\ManagerBundle\Form\Type\ExportJobType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Dumper;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
class ExportHandler extends ExchangeHandler
{
    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->job = new ExchangeJob();
    }

    /**
     * @inheritdoc
     */
    public function createForm()
    {
        $allowedApps = $this->getAllowedApplications();
        $type        = new ExportJobType();
        return $this->container->get('form.factory')
                ->create($type, $this->job, array('application' => $allowedApps));
    }

    /**
     * @inheritdoc
     */
    public function bindForm()
    {
        $form    = $this->createForm();
        $request = $this->container->get('request');
        $form->bind($request);
        if ($form->isValid()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function makeJob()
    {
        gc_enable();
        $normalizer = new ExchangeNormalizer($this->container);
        $time = array(
            'start' => microtime(true)
        );
        $this->exportSources($normalizer);
        $time['sources'] = microtime(true);
        $time['sources'] = $time['sources'] . '/' . ($time['sources'] - $time['start']);

        gc_collect_cycles();
        $this->exportApps($normalizer);
        $time['end'] = microtime(true);
        $time['total'] = $time['end'] - $time['start'];
        gc_collect_cycles();
        $export = $normalizer->getExport();
        $export['time'] = $time;
//        die(print_r($time,1));
        return $export;
    }

    /**
     * Encode array to given format (YAML|JSON).
     *
     * @param $data
     * @return string
     */
    public function format($data)
    {
        if ($this->job->getFormat() === ExchangeJob::FORMAT_JSON) {
            return json_encode($data);
        } elseif ($this->job->getFormat() === ExchangeJob::FORMAT_YAML) {
            $dumper = new Dumper();
            $yaml   = $dumper->dump($data, 20);
            return $yaml;
        }
    }

    /**
     * @param $normalizer
     */
    private function exportApps(ExchangeNormalizer $normalizer)
    {
        $normalizer->handleValue($this->job->getApplication());
        gc_collect_cycles();
    }

    /**
     * @param $normalizer
     */
    private function exportSources(ExchangeNormalizer $normalizer)
    {
        $sources     = array();
        $application = $this->job->getApplication();
        $help        = $this->getAllowedApplicationSources($application);
        foreach ($help as $src) {
            if (isset($sources[ $src->getId() ])) {
                continue;
            }
            $normalizer->handleValue($src);
            gc_collect_cycles();
        }
    }

    /**
     * @return ExchangeJob
     */
    public function getJob()
    {
        return parent::getJob();
    }
}