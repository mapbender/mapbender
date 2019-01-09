<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Description of SourceInstanceEntityHandler
 *
 * @author Paul Schmidt
 *
 * @property SourceInstance $entity
 */
abstract class SourceInstanceEntityHandler extends EntityHandler
{
    /**
     * @param array $configuration
     * @return SourceInstance
     * @internal param SourceInstance $instance
     */
    abstract public function setParameters(array $configuration = array());

    /**
     * Copies attributes from bound instance's source to the bound instance
     * @deprecated
     * If the source is already bound to the instance....
     */
    abstract public function create();

    /**
     * @inheritdoc
     * @deprecated Should be inlined to controller. All instance types can use the same logic
     * @see \Mapbender\WmsBundle\Controller\RepositoryController::deleteInstanceAction()
     * @see \Mapbender\WmsBundle\Controller\RepositoryController::deleteAction()
     */
    public function remove()
    {
        $entityManager = $this->getEntityManager();
        $application = $this->entity->getLayerset()->getApplication();
        $application->setUpdated(new \DateTime('now'));
        $entityManager->persist($application);
        $entityManager->remove($this->entity);
    }

    /**
     * Update instance parameters
     */
    abstract public function update();
    
    /**
     * Returns the instance configuration with signed urls or null if an instance configuration isn't valid.
     * @return array instance configuration or null
     */
    abstract public function getConfiguration(Signer $signer);
    
    /**
     * Does nothing, returns nothing, is never called
     * @deprecated
     */
    public function generateConfiguration()
    {
    }

    /**
     * Returns ALL vendorspecific parameters, NOT just the hidden ones
     * @return string[]
     */
    abstract public function getSensitiveVendorSpecific();

    /**
     * Returns a source config generating service appropriate for the bound source instance (polymorphic).
     *
     * @return SourceService
     */
    protected function getService()
    {
        /** @var TypeDirectoryService $directory */
        $directory = $this->container->get('mapbender.source.typedirectory.service');
        return $directory->getSourceService($this->entity);
    }
}
