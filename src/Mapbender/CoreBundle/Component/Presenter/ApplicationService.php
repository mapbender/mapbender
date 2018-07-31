<?php


namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Entity\Element as ElementEntity;
use Mapbender\CoreBundle\Component\Element as ElementComponent;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Component\Application as ApplicationComponent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use FOM\UserBundle\Component\AclManager;
use Mapbender\CoreBundle\Component\SecurityContext;

/**
 * Presentation service for Mapbender Application entities.
 *
 * Currently only performs grants checks and Element Component factory duties.
 */
class ApplicationService
{
    /** @var ContainerInterface */
    protected $container;
    /** @var SecurityContext  */
    protected $securityContext;
    /** @var AclManager */
    protected $aclManager;
    /** @var ApplicationComponent[] */
    protected $bufferedApplicationComponents = array();


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->securityContext = $container->get('security.context');
        $this->aclManager = $container->get("fom.acl.manager");
    }

    /**
     * Returns the list of Elements from the given Application that are enabled and granted for the current user
     * @param ApplicationEntity $entity
     * @return ElementComponent[]
     */
    public function getActiveElements(ApplicationEntity $entity)
    {
        $elements    = array();
        foreach ($entity->getElements() as $elementEntity) {
            if (!$elementEntity->getEnabled() || !$this->isElementGranted($elementEntity)) {
                continue;
            }
            $elementComponent = $this->makeElementComponent($entity, $elementEntity);
            if ($elementComponent) {
                $elements[] = $elementComponent;
            }
        }
        return $elements;
    }

    /**
     * @param ApplicationEntity $application
     * @param ElementEntity $entity
     * @return ElementComponent|null
     */
    protected function makeElementComponent(ApplicationEntity $application, ElementEntity $entity)
    {
        $class = $entity->getClass();
        if (!class_exists($class)) {
            // @todo: warn, maybe?
            return null;
        }
        $appComponent = $this->getApplicationComponent($application);
        return new $class($appComponent, $this->container, $entity);
    }

    /**
     * Gets (potentially reuses) a (dummy?) Application Component. This is only used for binding to an ElementComponent.
     *
     * @todo: figure out how and why this is even used on the ElementComponent side
     *
     * @param ApplicationEntity $application
     * @param bool $reuseBuffered to reuse already fabbed Application Component
     * @return ApplicationComponent
     */
    protected function getApplicationComponent(ApplicationEntity $application, $reuseBuffered = true)
    {
        if ($reuseBuffered) {
            $appId = spl_object_hash($application);
            if (empty($this->bufferedApplicationComponents[$appId])) {
                $appComponent = $this->makeApplicationComponent($application);
                $this->bufferedApplicationComponents[$appId] = $appComponent;
            }
            return $this->bufferedApplicationComponents[$appId];
        } else {
            return $this->makeApplicationComponent($application);
        }
    }

    /**
     * Creates a (dummy?) Application Component. This is only used for binding to an ElementComponent.
     *
     * @todo: figure out how and why this is even used on the ElementComponent side
     *
     * @param ApplicationEntity $application
     * @return ApplicationComponent
     */
    protected function makeApplicationComponent(ApplicationEntity $application)
    {
        return new ApplicationComponent($this->container, $application);
    }

    /**
     * @param ElementEntity $element
     * @param string $permission
     * @return bool
     */
    protected function isElementGranted(ElementEntity $element, $permission = SecurityContext::PERMISSION_VIEW)
    {
        if ($this->aclManager->hasObjectAclEntries($element)) {
            $isGranted = $this->securityContext->isGranted($permission, $element);
        } else {
            $isGranted = true;
        }

        if (!$isGranted && $element->getApplication()->isYamlBased()) {
            foreach ($element->getYamlRoles() ?: array() as $role) {
                if ($this->securityContext->isGranted($role)) {
                    $isGranted = true;
                    break;
                }
            }
        }
        return $isGranted;
    }
}
