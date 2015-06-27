<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
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
        $this->job->setApplications($allowedApps);
        $type = new ExportJobType();
        return $this->container->get('form.factory')
            ->create($type, $this->job, array('applications' => $this->job->getApplications()));
    }

    /**
     * @inheritdoc
     */
    public function bindForm()
    {
        $form = $this->createForm();
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
        $normalizer = new ExchangeNormalizer($this->container);
        $this->exportSources($normalizer);
        $this->exportApps($normalizer);
        return $normalizer->getExport();
    }

    public function format($scr)
    {
        if ($this->job->getFormat() === ExchangeJob::FORMAT_JSON) {
            return json_encode($scr);
        } elseif ($this->job->getFormat() === ExchangeJob::FORMAT_YAML) {
            $dumper = new Dumper();
            $yaml = $dumper->dump($scr, 20);
            return $yaml;
        }
    }

    private function exportApps($normalizer)
    {
        $data = array();
        foreach ($this->job->getApplications() as $app) {
            $data[] = $normalizer->handleValue($app);
        }
        return $data;
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
        $data = array();
        $sources = new ArrayCollection();
        if ($this->job->getAddSources()) {
            $sources = $this->getAllowedSources();
        } else {
            foreach ($this->job->getApplications() as $app) {
                $help = $this->getAllowedApplicationSources($app);
                foreach ($help as $src) {
                    if ($src->getId() && !$sources->contains($src)) {
                        $sources->add($src);
                    }
                }
            }
        }
        foreach ($sources as $source) {
            $data[] = $normalizer->handleValue($source);
        }
        return $data;
    }
}
