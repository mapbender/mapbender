<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
abstract class ExchangeHandler
{

    const CONTENT_APP = 'aplication';
    const CONTENT_ACL = 'acl';
    const CONTENT_SOURCE = 'source';

    protected $securityContext;
    protected $container;
    protected $job;
    protected $mapper;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->mapper = array(
            ExchangeHandler::CONTENT_APP => array(),
            ExchangeHandler::CONTENT_ACL => array(),
            ExchangeHandler::CONTENT_SOURCE => array()
        );
        $this->securityContext = $this->container->get('security.context');
    }

    protected function getAllowedAppllications()
    {
        $allowed_apps = EntityHandler::findAll(
                $this->container, "Mapbender\CoreBundle\Entity\Application", array(), "EDIT");
        return $allowed_apps;
    }

//
    protected function getAllowedApplicationSources(Application $app, $action = 'EDIT')
    {
        $sources = new ArrayCollection();
        if (true === $this->isGranted($action, $app)) {
            foreach ($app->getLayersets() as $layerset) {
                foreach ($layerset->getInstances() as $instance) {
                    $source = $instance->getSource();
                    if ($this->isGranted('EDIT', $source)) {
                        $sources->add($source);
                    }
                }
            }
        }
        return $sources;
    }

    protected function getAllowedSources()
    {
        $allowed_sources = new ArrayCollection();
        if ($this->isGranted("EDIT", "Mapbender\CoreBundle\Entity\Source")) {
            $allowed_sources = EntityHandler::findAll(
                    $this->container, "Mapbender\CoreBundle\Entity\Source", array());
        }
        return $allowed_sources;
    }

    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function setJob($job)
    {
        $this->job = $job;
        return $this;
    }

    public function getMapper()
    {
        return $this->mapper;
    }

    public function setMapper($mapper)
    {
        $this->mapper = $mapper;
        return $this;
    }

    /**
     * Checks the grant for an action and an object
     * 
     * @param \Object $object the object 
     * @throws AccessDeniedException
     */
    public function checkGranted($action, $object)
    {
        $gr = $this->securityContext->isGranted($action, $object);
        if ($action === "CREATE") {
            $oid = new ObjectIdentity('class', get_class($object));
            if (false === $this->securityContext->isGranted($action, $oid)) {
                throw new AccessDeniedException();
            }
        } else if ($action === "VIEW" && !$this->securityContext->isGranted($action, $object)) {
            throw new AccessDeniedException();
        } else if ($action === "EDIT" && !$this->securityContext->isGranted($action, $object)) {
            throw new AccessDeniedException();
        } else if ($action === "DELETE" && !$this->securityContext->isGranted($action, $object)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * Checks the grant for an action and an object
     *
     * @param string $action action, for example "CREATE"
     * @param \Object $object the object 
     * @throws AccessDeniedException
     */
    public function isGranted($action, $object)
    {
        try {
            $this->checkGranted($action, $object);
            return true;
        } catch (AccessDeniedException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Creates a Job form
     */
    abstract public function createForm();

    /**
     * Bind a Job form
     */
    abstract public function bindForm();

    /**
     * Bind a Job form
     */
    abstract public function makeJob();
}
