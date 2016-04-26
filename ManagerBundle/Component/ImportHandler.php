<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\PersistentCollection;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Form\Type\ImportJobType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * Description of ImportHandler
 *
 * @author Paul Schmidt
 */
class ImportHandler extends ExchangeHandler
{
    protected $denormalizer;

    protected $toCopy;

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container, $toCopy = false)
    {
        parent::__construct($container);
        $this->job = new ImportJob();
        $this->toCopy = $toCopy;
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
            $apps = $this->importApps($import);
            $em->flush();
            foreach ($apps as $app) {
                $this->addAcls($app);
            }
            $em->getConnection()->commit();
            $em->clear();
//            if (isset($import[self::CONTENT_ACL])) {
//                $this->importAcls($import[self::CONTENT_ACL]);
//            }
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $em->clear();
            throw new ImportException($this->container->get('translator')
                ->trans('mb.manager.import.application.failed', array()) . " -> " . $e->getMessage());
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
                    $classMeta = $this->denormalizer->getClassMetadata($class);
                    if (!$this->toCopy) {
                        $criteria = $this->denormalizer->getIdentCriteria(
                            $item,
                            $classMeta,
                            true,
                            array('title', 'type', 'name', 'onlineResource')
                        );
                        if (isset($criteria['id'])) {
                            unset($criteria['id']);
                        }
                        $sources    = $this->denormalizer->findEntities($class, $criteria);
                        if (!$this->findSourceToMapper($sources, $item, 0)) {
                            $source = $this->denormalizer->handleData($item, $class);
                            $em->persist($source);
                            $em->flush();
                        }
                    } else {
                        $criteria = $this->denormalizer->getIdentCriteria($item, $classMeta, true, array());
                        $sources  = $this->denormalizer->findEntities($class, $criteria);
                        $result = array();
                        $this->addSourceToMapper($sources[0], $item, $result);
                        $this->mergeIntoMapper($result);
                    }
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
        $apps = array();
        $em = $this->container->get('doctrine')->getManager();
        foreach ($data as $class => $content) { # add entities
            if ($this->denormalizer->findSuperClass($class, 'Mapbender\CoreBundle\Entity\Application')) {
                foreach ($content as $item) {
                    $app         = $this->denormalizer->handleData($item, $class);
                    $app->setScreenshot(null)->setSource(Application::SOURCE_DB);
                    $em->persist($app);
                    $em->flush();
                    $this->denormalizer->generateElementConfiguration($app);
                    $apps[] = $app;
                }
            }
        }
        return $apps;
    }

    private function addAcls($object)
    {
        $aces = array();
        // add current user as owner for this object
        $current_user       = $this->container->get('security.context')->getToken()->getUser();
        $current_user_identity = UserSecurityIdentity::fromAccount($current_user);
        $aces[] = array(
            'sid' => $current_user_identity,
            'mask' => MaskBuilder::MASK_OWNER
        );
        // copy ACEs from orig $object
        if ($this->toCopy) {
            $class = $this->denormalizer->getRealClass($object);
            $classMeta = $this->denormalizer->getClassMetadata($class);
            $after_criteria = $this->denormalizer->getIdentCriteria($object, $classMeta, false, array());
            $orig_criteria = $this->denormalizer->getBeforeFromAfter($class, $after_criteria);
            $orig_object  = $this->denormalizer->findEntities($class, $orig_criteria);
            $provider = $this->container->get('security.acl.provider');
            $acl = $provider->findAcl(ObjectIdentity::fromDomainObject($orig_object[0]));
            foreach ($acl->getObjectAces() as $ace) {
                $user_sec_ident = $ace->getSecurityIdentity();
                if (!$current_user_identity->equals($user_sec_ident)) {
                    $aces[] = array(
                        'sid' => $user_sec_ident,
                        'mask' => $ace->getMask()
                    );
                }
            }
        }
        $this->container->get('fom.acl.manager')->setObjectACL($object, $aces, 'object');
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

    private function findSourceToMapper(array $sources, array $item, $idx = 0)
    {
        if (count($sources) === 0) {
            return false;
        }
        try {
            $result = array();
            $this->addSourceToMapper($sources[$idx], $item, $result);
            $this->mergeIntoMapper($result);
            return true;
        } catch (\Exception $e) {
            $idx++;
            if ($idx < count($sources)) {
                $this->findSourceToMapper($sources, $item, $idx);
            } else {
                return false;
            }
        }
    }

    /**
     * Adds entitiy with assoc. items to mapper.
     *
     * @param object $object source
     */
    private function addSourceToMapper($object, array $data, array &$result)
    {
        $em = $this->container->get('doctrine')->getManager();
        $em->refresh($object);
        if (!$em->contains($object)) {
             $em->merge($object);
        }
        $classMeta = $this->denormalizer->getClassMetadata($this->denormalizer->getRealClass($object));
        $criteriaAfter  = $this->denormalizer->getIdentCriteria($object, $classMeta);
        $criteriaBefore  = $this->denormalizer->getIdentCriteria($data, $classMeta);
        $realClass = $this->denormalizer->getRealClass($object);
        $result[$realClass][] =
            array('before' => $criteriaBefore, 'after' => array( 'criteria' => $criteriaAfter, 'object' => $object));
        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            $fieldName = $assocItem['fieldName'];
            $getMethod = $this->denormalizer->getReturnMethod($fieldName, $classMeta->getReflectionClass());
            if ($getMethod) {
                $subObject = $getMethod->invoke($object);
                $num = 0;
                if ($subObject instanceof PersistentCollection) {
                    if ($this->denormalizer
                        ->findSuperClass($assocItem['targetEntity'], "Mapbender\CoreBundle\Entity\Keyword")) {
                        continue;
                    } elseif (!isset($data[$fieldName]) || count($data[$fieldName]) !== $subObject->count()) {
                        throw new \Exception('no filed name at normalized data');
                    }
                    foreach ($subObject as $item) {
                        if ($this->denormalizer->findSuperClass($item, 'Mapbender\CoreBundle\Entity\SourceItem')) {
                            $subdata = $data[$fieldName][$num];
                            if ($classDef = $this->denormalizer->getClassDifinition($subdata)) {
                                $em->getRepository($classDef[0]);
                                $meta     = $this->denormalizer->getClassMetadata($classDef[0]);
                                $criteria = $this->denormalizer->getIdentCriteria($subdata, $meta);
                                $od = null;
                                if ($this->denormalizer->isReference($subdata, $criteria)) {
                                    if ($od = $this->denormalizer->getAfterFromBefore($classDef[0], $criteria)) {
                                        ;
                                    } elseif ($od = $this->denormalizer->getEntityData($classDef[0], $criteria)) {
                                        ;
                                    }
                                }
                                $this->addSourceToMapper($item, $od, $result);
                                $num++;
                            } else {
                                throw new \Exception('no class definition at normalized data');
                            }
                        }
                    }
                }
            }
        }
    }

    private function mergeIntoMapper(array $mapper)
    {
        foreach ($mapper as $class => $content) {
            foreach ($content as $item) {
                $this->denormalizer->addToMapper($item['after']['object'], $item['before'], $item['after']['criteria']);
            }
        }
    }
}
