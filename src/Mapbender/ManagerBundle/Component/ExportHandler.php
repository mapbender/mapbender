<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Entity\Application;
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
        $this->checkGranted('EDIT', new Application());
        $allowed_apps = $this->getAllowedAppllications();
        $type = new ExportJobType();
        return $this->container->get('form.factory')->create($type, $this->job, array('applications' => $allowed_apps));
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
        $export = array();
        $export[self::CONTENT_SOURCE] = $this->exportSources();
        $export[self::CONTENT_APP] = $this->exportApps();
//        # TODO  $export[ExchangeHandler::$CONTENT_ACL] = $this->exportAcls();
        return $export;
    }

    public function format($scr)
    {
        if ($this->job->getFormat() === ExchangeJob::FORMAT_JSON) {
            return json_encode($scr);
        } else if ($this->job->getFormat() === ExchangeJob::FORMAT_YAML) {
            $dumper = new Dumper();
            $yaml = $dumper->dump($scr, 20);
            return $yaml;
        }
    }

    private function exportApps()
    {
        $arr = array();
        $normalizer = new ExchangeNormalizer($this->container);
        foreach ($this->job->getApplications() as $app) {
            $arr_ = $normalizer->normalize($app);
            $arr[] = $arr_;
        }
        return $arr;
    }

    private function exportAcls()
    {
        $arr = array();
        if ($this->job->getAcl()) {
            // TODO
        }
        return $arr;
    }

    private function exportSources()
    {
        $data = array();
        $sources = new ArrayCollection();
        if ($this->job->getAddSources()) {
            $sources = $this->getAllowedSources();
            
        } else {
            foreach ($this->job->getApplications() as $app) {
                $help = $this->getAllowedApplicationSources($app);
                foreach ($help as $src){
                    if($src->getId() && !$sources->contains($src)){
                        $sources->add($src);
                    }
                }
            }
        }
        $normalizer = new ExchangeNormalizer($this->container);
        foreach ($sources as $source) {
            $arr = $normalizer->normalize($source);
            $data[] = $arr;
        }
        return $data;
    }

}
