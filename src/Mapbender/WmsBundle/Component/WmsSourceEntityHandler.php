<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsSource;

/**
 * Description of WmsSourceEntityHandler
 *
 * @author Paul Schmidt
 *
 * @property WmsSource $entity
 */
class WmsSourceEntityHandler extends SourceEntityHandler
{
    /**
     * Creates a new WmsInstance from the bound WmsSource entity
     *
     * @return WmsInstance
     */
    public function createInstance()
    {
        $instance = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->populateFromSource($this->entity);
        /** @var TypeDirectoryService $directory */
        $directory = $this->container->get('mapbender.source.typedirectory.service');
        $directory->getSourceService($instance)->initializeInstance($instance);
        return $instance;
    }

    /**
     * Update a source from a new source
     *
     * @param WmsSource|Source $sourceNew
     */
    public function update(Source $sourceNew)
    {
        $em = $this->getEntityManager();
        $transaction = $em->getConnection()->isTransactionActive();
        if (!$transaction) {
            $em->getConnection()->beginTransaction();
        }
        /* Update source attributes */
        $classMeta = $em->getClassMetadata(EntityUtil::getRealClass($this->entity));

        foreach ($classMeta->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $classMeta->getIdentifier())
                    && ($getter = EntityUtil::getReturnMethod($fieldName, $classMeta->getReflectionClass()))
                    && ($setter = EntityUtil::getSetMethod($fieldName, $classMeta->getReflectionClass()))) {
                $value     = $getter->invoke($sourceNew);
                $setter->invoke($this->entity, is_object($value) ? clone $value : $value);
            } elseif (($getter = EntityUtil::getReturnMethod($fieldName, $classMeta->getReflectionClass()))
                    && ($setter = EntityUtil::getSetMethod($fieldName, $classMeta->getReflectionClass()))) {
                if (!$getter->invoke($this->entity) && ($new = $getter->invoke($sourceNew))) {
                    $setter->invoke($this->entity, $new);
                }
            }
        }

        $contact = clone $sourceNew->getContact();
        $em->detach($contact);
        if ($this->entity->getContact()) {
            $em->remove($this->entity->getContact());
        }
        $this->entity->setContact($contact);

        $rootUpdateHandler = new WmsLayerSourceEntityHandler($this->container, $this->entity->getRootlayer());
        $rootUpdateHandler->update($sourceNew->getRootlayer());

        KeywordUpdater::updateKeywords(
            $this->entity,
            $sourceNew,
            $em,
            'Mapbender\WmsBundle\Entity\WmsSourceKeyword'
        );

        foreach ($this->entity->getInstances() as $instance) {
            $instanceUpdateHandler = new WmsInstanceEntityHandler($this->container, $instance);
            $instanceUpdateHandler->update();
        }

        if (!$transaction) {
            $em->getConnection()->commit();
        }
    }

    /**
     * Checks if service has auth information that needs to be hidden from client.
     *
     * @param WmsSource $source
     * @return bool
     */
    public static function useTunnel(WmsSource $source)
    {
        return !!$source->getUsername();
    }
}
