<?php

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
abstract class ExchangeHandler
{
    const CONTENT_APP    = 'aplication';
    const CONTENT_ACL    = 'acl';
    const CONTENT_SOURCE = 'source';

    /** @var SecurityContext */
    protected $securityContext;

    /** @var ContainerInterface  */
    protected $container;

    /** @var  ImportJob */
    protected $job;

    protected $mapper;

    /**
     * ExchangeHandler constructor.
     *
     * @param ContainerInterface $container
     */
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

    /**
     * Get current user allowed applications
     *
     * @return Application[]|ArrayCollection
     */
    protected function getAllowedApplications()
    {
        return EntityHandler::findAll(
            $this->container,
            'Mapbender\CoreBundle\Entity\Application',
            array(),
            SecurityContext::PERMISSION_EDIT);
    }

    /**
     * Get current user allowed application sources
     *
     * @param Application $app
     * @param string      $action
     * @return Source[]|ArrayCollection
     */
    protected function getAllowedApplicationSources(Application $app, $action = SecurityContext::PERMISSION_EDIT)
    {
        $sources = new ArrayCollection();
        if ($this->securityContext->checkGranted($action, $app)) {
            foreach ($app->getLayersets() as $layerSet) {
                foreach ($layerSet->getInstances() as $instance) {
                    $source = $instance->getSource();
                    if ($this->getSecurityContext()->isUserAllowedToEdit($source)) {
                        $sources->add($source);
                    }
                }
            }
        }
        return $sources;
    }

    /**
     * Get allowed sources
     *
     * @return Source[]|ArrayCollection
     */
    protected function getAllowedSources()
    {
        $allowedSources = new ArrayCollection();
        $sourceClass    = 'Mapbender\CoreBundle\Entity\Source';
        if ($this->securityContext->isUserAllowedToCreate($sourceClass)) {
            $allowedSources = EntityHandler::findAll(
                $this->container,
                $sourceClass,
                array(),
                SecurityContext::PERMISSION_CREATE);
        }
        return $allowedSources;
    }

    /**
     * @return SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }


    /**
     * @return ImportJob
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param $job
     * @return $this
     */
    public function setJob($job)
    {
        $this->job = $job;
        return $this;
    }

    /**
     * @return array
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @param $mapper
     * @return $this
     */
    public function setMapper($mapper)
    {
        $this->mapper = $mapper;
        return $this;
    }

    /**
     * Checks the grant for an action and an object
     *
     * @param string  $action action, for example "CREATE"
     * @param \Object $object the object
     * @return bool
     * @throws AccessDeniedException
     */
    public function isGranted($action, $object)
    {
        return $this->securityContext->checkGranted($action, $object, false);
    }

    /**
     * Creates a Job form
     * @return FormInterface
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
