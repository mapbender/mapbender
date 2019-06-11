<?php


namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Component;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
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
    /** @var UploadsManager */
    protected $uploadsManager;
    /** @var AuthorizationCheckerInterface  */
    protected $authorizationChecker;
    /** @var AclProviderInterface */
    protected $aclProvider;


    public function __construct(ElementFactory $elementFactory,
                                UploadsManager $uploadsManager,
                                AuthorizationCheckerInterface $authorizationChecker,
                                AclProviderInterface $aclProvider)
    {
        $this->elementFactory = $elementFactory;
        $this->uploadsManager = $uploadsManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->aclProvider = $aclProvider;
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
     * @return UploadsManager
     */
    public function getUploadsManager()
    {
        return $this->uploadsManager;
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
    protected function isElementGranted(Entity\Element $element, $permission = 'VIEW')
    {
        $oid = ObjectIdentity::fromDomainObject($element);
        try {
            $acl = $this->aclProvider->findAcl($oid);
            if ($acl->getObjectAces()) {
                $isGranted = $this->authorizationChecker->isGranted($permission, $element);
            } else {
                $isGranted = true;
            }
        } catch (AclNotFoundException $e) {
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
