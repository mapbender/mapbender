<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Mapbender\CoreBundle\Component\Application as AppComponent;
use Mapbender\ManagerBundle\Form\Type\ImportJobType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of ImportHandler
 *
 * @author Paul Schmidt
 */
class ImportHandler extends ExchangeHandler
{
    public static $MAP_SOURCE_ITEM = 'sourceItem';
    
    
    protected $denormalizer;
    
    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->job = new ImportJob();
    }
    
    /**
     * @inheritdoc
     */
    public function createForm()
    {
        $this->isGrantedCreate();
//        $allowed_apps = $this->getAllowedAppllications();
        $type = new ImportJobType();
        return $this->container->get('form.factory')->create($type, $this->job, array());
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
        $em = $this->container->get('doctrine')->getManager();
        $this->denormalizer = new ExchangeDenormalizer($em, $this->mapper);
        try{
            $em->getConnection()->beginTransaction();
            $import = $this->job->getImportContent();
            $this->importApps($import[ExchangeHandler::$CONTENT_APP]);
//            $this->importAcls($import[ExchangeHandler::$CONTENT_ACL]);
//            $this->importSources($import[ExchangeHandler::$CONTENT_SOURCE]);
            $em->getConnection()->commit();
        } catch (\Exception $e){
            $em->getConnection()->rollback();
        }
    }
    
    private function importApps($data)
    {
        foreach ($data as $appdata){
            $class = $appdata['__class__'][0];
//            $appObj = new $class();
            $appdata['slug'] = AppComponent::generateSlug($this->container, $appdata['slug']);
            $this->denormalizer->denormalize($appdata, $class);
            $a = 0;
        }
    }
    
    private function importAcls($data)
    {
        
    }
    
    private function importSources($data)
    {
        
    }
}
