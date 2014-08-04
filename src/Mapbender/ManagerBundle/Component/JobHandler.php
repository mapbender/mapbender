<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
abstract class JobHandler
{

    protected $securityContext;
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
        $this->securityContext = $this->container->get('security.context');
    }

    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setSecurityContext($securityContext)
    {
        $this->securityContext = $securityContext;
        return $this;
    }

    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Creates a Job form
     */
    abstract public function createForm(ExportJob $expJob);
    
    /**
     * Bind a Job form
     */
    abstract public function bindForm();
//    
//    /**
//     * Bind a Job form
//     */
//    abstract public function execute(Job $job);

    /**
     * Checks the grant for an action and an object
     *
     * @param \Object $object the object 
     * @throws AccessDeniedException
     */
    public function checkGranted($action, $object)
    {
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

}
