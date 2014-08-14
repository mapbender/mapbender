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
        $this->denormalizer = new ExchangeDenormalizer($this->container, $this->mapper);
        try {
            $em->getConnection()->beginTransaction();
            $import = $this->job->getImportContent();
            if (isset($import[self::CONTENT_SOURCE])) {
                $this->importSources($import[self::CONTENT_SOURCE]);
            }
            if (isset($import[self::CONTENT_APP])) {
                $this->importApps($import[self::CONTENT_APP]);
            }
            if (isset($import[self::CONTENT_ACL])) {
                $this->importAcls($import[self::CONTENT_ACL]);
            }
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
        }
        die();
    }

    private function importApps($data)
    {
        foreach ($data as $item) {
            $class = $this->denormalizer->getClassName($item);
            $item[ExchangeSerializer::KEY_SLUG] = AppComponent::generateSlug($this->container, $item[ExchangeSerializer::KEY_SLUG], 'imp');
            $app = $this->denormalizer->denormalize($item, $class);
            $this->denormalizer->generateElementConfiguration($app);
            $a = 0;
        }
    }

    private function importAcls($data)
    {
        foreach ($data as $item) {
            $class = $this->denormalizer->getClassName($item);
            $this->denormalizer->denormalize($item, $class);
            $a = 0;
        }
    }

    private function importSources($data)
    {
        foreach ($data as $item) {
            $source = isset($item[ExchangeSerializer::KEY_IDENTIFIER]) ? $this->findSource($item[ExchangeSerializer::KEY_IDENTIFIER]) : null;
            $class = $this->denormalizer->getClassName($item);
            if (!$source) {
                $this->denormalizer->denormalize($item, $class);
            } else {
                $this->denormalizer->mapSource($item, $class, $source);
            }
        }
    }

    protected function findSource($identifier)
    {
        foreach ($this->getAllowedSources() as $sourceHelp) {
            if($sourceHelp->getIdentifier() === $identifier){
                return $sourceHelp;
            }
        }
        return null;
    }

}
