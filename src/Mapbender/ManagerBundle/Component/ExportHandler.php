<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Form\Type\ExportJobType;
use Mapbender\ManagerBundle\Component\Job;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
class ExportHandler extends JobHandler
{

    public function __construct($container)
    {
        parent::__construct($container);
    }

    private function isGrantedCreate()
    {
        $application = new Application();
        // ACL access check
        $this->checkGranted('CREATE', $application);
    }

    private function getAllowedAppllications($all = false)
    {
        $applications = $this->getContainer()->get('mapbender')->getApplicationEntities();
        $allowed_apps = new ArrayCollection();
        foreach ($applications as $application) {
            if ($all || $this->isGranted('EDIT', $application)) {
                $allowed_apps->add($application);
            }
        }
        return $allowed_apps;
    }

    public function createForm(ExportJob $expJob)
    {
        $this->isGrantedCreate();
        $allowed_apps = $this->getAllowedAppllications();
        $type = new ExportJobType();
        return $this->container->get('form.factory')->create($type, $expJob, array('applications' => $allowed_apps));
    }

    public function bindForm()
    {
        $expJob = new ExportJob();
        $form = $this->createForm($expJob);
        $request = $this->container->get('request');
        $form->bind($request);
        if ($form->isValid()) {
            $export = array();
            $export["applications"] = $this->exportApps($expJob);
            $export["acls"] = $this->exportAcls($expJob);
            $export["sources"] = $this->exportSources($expJob);
            return $export;
        }
    }
    
    public function exportApps(Job $job)
    {
        $arr = array();
        foreach ($job->getApplications() as $app) {
            $arr_ = $app->toArray();
            $arr[] = $arr_;
        }
        return $arr;
    }
    
    public function exportAcls(Job $job)
    {
        $arr = array();
        if($job->getAcl()){
            // TODO
        }
        return $arr;
    }
    
    public function exportSources(Job $job)
    {
        $arr = array();
        if($job->getAcl()){
            // TODO
        }
        return $arr;
    }
    
}
