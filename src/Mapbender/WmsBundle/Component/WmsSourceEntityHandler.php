<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;

/**
 * Description of WmsSourceEntityHandler
 *
 * @author Paul Schmidt
 */
class WmsSourceEntityHandler extends SourceEntityHandler
{
    /** @var  WmsSource */
    protected $entity;

    /**
     * @inheritdoc
     */
    public function create()
    {

    }

    /**
     * Creates a new WmsInstance, optionally attaches it to a layerset, then updates
     * the ordering of the layers.
     *
     * @param Layerset|null $layerSet new instance will be attached to layerset if given
     * @return WmsInstance
     */
    public function createInstance(Layerset $layerSet = null)
    {
        $instance = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->populateFromSource($this->entity);
        if ($layerSet) {
            $instance->setLayerset($layerSet);
            $num = 0;
            foreach ($layerSet->getInstances() as $instanceAtLayerset) {
                /** @var WmsInstance $instanceAtLayerset */
                $instanceAtLayerset->setWeight($num);
                $num++;
            }
        }
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
     * Find the named WmsLayerSource in the given WmsSource
     *
     * @param WmsSource $source
     * @param string $layerName
     * @return WmsLayerSource|null
     */
    public static function getLayerSourceByName(WmsSource $source, $layerName)
    {
        foreach ($source->getLayers() as $layer) {
            if ($layer->getName() == $layerName) {
                return $layer;
            }
        }
        return null;
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
