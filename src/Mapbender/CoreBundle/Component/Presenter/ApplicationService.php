<?php


namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Component;
use FOM\UserBundle\Component\AclManager;
use Mapbender\CoreBundle\Component\SecurityContext;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Presentation service for Mapbender Application entities.
 *
 * Currently only performs grants and enabled checks to filter the list of Elements
 * comprising the Application for rendering / emission of Element assets.
 */
class ApplicationService
{
    /** @var ElementFactory */
    protected $elementFactory;
    /** @var AuthorizationCheckerInterface  */
    protected $authorizationChecker;
    /** @var AclManager */
    protected $aclManager;
    /** @var Component\Application[] */
    protected $bufferedApplicationComponents = array();


    public function __construct(ElementFactory $elementFactory,
                                AuthorizationCheckerInterface $authorizationChecker,
                                AclManager $aclManager)
    {
        $this->elementFactory = $elementFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->aclManager = $aclManager;
    }

    /**
     * Returns the list of Elements from the given Application that are enabled and (optionally) granted for
     * the current user.
     *
     * @param Entity\Application $entity
     * @param bool $requireGrant return only VIEW-granted entries (default true)
     * @return Component\Element[]
     */
    public function getActiveElements(Entity\Application $entity, $requireGrant = true)
    {
        $activeEntities = $this->filterDisplayableElements($entity->getElements(), $requireGrant);
        return $this->getDisplayableElementComponents($activeEntities);
    }

    /**
     * @param Entity\Application $application
     * @param string $elementId
     * @return Component\Element|null
     */
    public function getSingleElementComponent(Entity\Application $application, $elementId)
    {
        // @todo: put YAML applications into a proper object repository, so we can get the Element entity from Doctrine
        $entities = $this->filterDisplayableElements($application->getElements());
        foreach ($entities as $entity) {
            if ($entity->getId() == $elementId) {
                return $this->elementFactory->componentFromEntity($entity);
            }
        }
        return null;
    }

    /**
     * @param Entity\Element[] $entities
     * @return Component\Element[]
     */
    protected function getDisplayableElementComponents($entities)
    {
        $components = array();
        foreach ($entities as $entity) {
            try {
                $components[] = $this->elementFactory->componentFromEntity($entity, true);
            } catch (ElementErrorException $e) {
                // for frontend presentation, incomplete / invalid elements are silently suppressed
                // => do nothing
            }
        }
        return $components;
    }

    /**
     * @param Entity\Element[] $entities
     * @param bool $requireGrant
     * @return Entity\Element[]
     */
    protected function filterDisplayableElements($entities, $requireGrant = true)
    {
        $entitiesOut = array();
        foreach ($entities as $entity) {
            if ($entity->getEnabled() && (!$requireGrant || $this->isElementGranted($entity))) {
                $entitiesOut[] = $entity;
            }
        }
        return $entitiesOut;
    }

    /**
     * @param Entity\Element $element
     * @param string $permission
     * @return bool
     */
    protected function isElementGranted(Entity\Element $element, $permission = SecurityContext::PERMISSION_VIEW)
    {
        if ($this->aclManager->hasObjectAclEntries($element)) {
            $isGranted = $this->authorizationChecker->isGranted($permission, $element);
        } else {
            $isGranted = true;
        }

        if (!$isGranted && $element->getApplication()->isYamlBased()) {
            foreach ($element->getYamlRoles() ?: array() as $role) {
                if ($this->authorizationChecker->isGranted($role)) {
                    $isGranted = true;
                    break;
                }
            }
        }
        return $isGranted;
    }
}
