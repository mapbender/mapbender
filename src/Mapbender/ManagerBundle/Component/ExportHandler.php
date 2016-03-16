<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Mapbender\ManagerBundle\Component\ExchangeNormalizer;
use Mapbender\ManagerBundle\Component\ExchangeJob;
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
        $allowedApps = $this->getAllowedAppllications();
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

    public function format($scr)
    {
        if ($this->job->getFormat() === ExchangeJob::FORMAT_JSON) {
            return json_encode($scr);
        } elseif ($this->job->getFormat() === ExchangeJob::FORMAT_YAML) {
            $dumper = new Dumper();
            $yaml   = $dumper->dump($scr, 20);
            return $yaml;
        }
    }

    private function exportApps($normalizer)
    {
        $normalizer->handleValue($this->job->getApplication());
        gc_collect_cycles();
    }

    private function exportAcls()
    {
        throw new \Exception('"exportAcls" is not implemented yet');
        if ($this->job->getAcl()) {
            // TODO
        }
    }

    private function exportSources($normalizer)
    {
        $sources = array();
        $help    = $this->getAllowedApplicationSources($this->job->getApplication());
        foreach ($help as $src) {
            if (!isset($sources[$src->getId()])) {
                $normalizer->handleValue($src);
                gc_collect_cycles();
            }
        }
    }
}