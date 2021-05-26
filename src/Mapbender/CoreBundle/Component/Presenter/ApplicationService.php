<?php


namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Component;
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

    public function __construct(ElementFactory $elementFactory,
                                UploadsManager $uploadsManager,
                                AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->elementFactory = $elementFactory;
        $this->uploadsManager = $uploadsManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Returns the list of Elements from the given Application that are enabled and (optionally) granted for
     * the current user.
     *
     * @param Entity\Application $application
     * @param bool $requireGrant return only VIEW-granted entries (default true)
     * @return Entity\Element[]
     * @todo: resolve copy&paste ApplicationService vs ApplicationMarkupRenderer
     */
    public function prepareElements(Entity\Application $application, $requireGrant = true)
    {
        $elements = $this->filterDisplayableElements($application->getElements(), $requireGrant);
        foreach ($elements as $element) {
            if (!$element->getTitle()) {
                $element->setTitle($this->elementFactory->getDefaultTitle($element));
            }
        }
        return $elements;
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
     * @param bool $requireGrant
     * @return Entity\Element[]
     */
    protected function filterDisplayableElements($entities, $requireGrant = true)
    {
        $entitiesOut = array();
        foreach ($entities as $entity) {
            $enabled = !$this->elementFactory->isTypeOfElementDisabled($entity) && $entity->getEnabled();
            if ($enabled && (!$requireGrant || $this->isElementGranted($entity))) {
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
        return $this->authorizationChecker->isGranted($permission, $element);
    }
}
