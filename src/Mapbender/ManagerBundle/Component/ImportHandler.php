<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
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
        $this->checkGranted('CREATE', new Application());
        $type = new ImportJobType();
        return $this->container->get('form.factory')->create($type, $this->job, array());
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
        $import             = $this->job->getImportContent();
        $this->denormalizer = new ExchangeDenormalizer($this->container, $this->mapper, $import);
        $em = $this->container->get('doctrine')->getManager();
        try {
            $em->clear();
            $em->getConnection()->beginTransaction();
            $this->importSources($import);
            $this->importApps($import);
            $em->flush();
            $em->getConnection()->commit();
            $em->clear();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $em->clear();
            throw new ImportException($this->container->get('translator')
                ->trans('mb.manager.import.application.failed', array()) . " -> " . $e->getMessage());
        }
        if (isset($import[self::CONTENT_ACL])) {
            $this->importAcls($import[self::CONTENT_ACL]);
        }
    }

    /**
     * Imports sources.
     * @param array $data data to import
     * @throws ImportException
     */
    private function importSources($data)
    {
        $em = $this->container->get('doctrine')->getManager();
        foreach ($data as $class => $content) {
            if ($this->denormalizer->findSuperClass($class, 'Mapbender\CoreBundle\Entity\Source')) {
                foreach ($content as $item) {
                    $classMeta = $em->getClassMetadata($class);
                    $criteria  = $this->denormalizer->getIdentCriteria($item, $classMeta, true, array('originUrl'));
                    if (isset($criteria['id'])) {
                        unset($criteria['id']);
                    }
                    $sources    = $this->denormalizer->findEntities($class, $criteria);
                    if (count($sources) === 0) {
                        $source = $this->denormalizer->handleData($item, $class);
                        $em->persist($source);
                    } else {
                        $source = $sources[0];
                        $this->denormalizer->addSourceToMapper($source);
                    }
                    $a = 0;
                }
            }
        }
    }

    /**
     * Imports applications.
     * @param array $data data to import
     * @throws ImportException
     */
    private function importApps($data)
    {
        $em = $this->container->get('doctrine')->getManager();
        foreach ($data as $class => $content) { # add entities
            if ($this->denormalizer->findSuperClass($class, 'Mapbender\CoreBundle\Entity\Application')) {
                foreach ($content as $item) {
                    $app = $this->denormalizer->handleData($item, $class);
                    $app->setScreenshot(null)
                        ->setSource(Application::SOURCE_DB);
                    $em->persist($app);
                    $this->denormalizer->generateElementConfiguration($app);
                }
            }
        }
    }

    /**
     * Imports ACLs.
     * @param array $data data to import
     * @throws ImportException
     */
    private function importAcls($data)
    {
        // TODO
    }
}
