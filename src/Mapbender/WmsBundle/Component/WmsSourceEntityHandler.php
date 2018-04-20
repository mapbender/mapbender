<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
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
     * @inheritdoc
     */
    public function save()
    {
        /** @var ObjectManager $manager */
        $manager = $this->container->get('doctrine')->getManager();
        $this->persistRecursive($manager, $this->entity);
    }

    /**
     * Persists the source, all keywords and all layers, recursively.
     *
     * @param ObjectManager $manager
     * @param WmsSource $entity
     */
    private static function persistRecursive(ObjectManager $manager, WmsSource $entity)
    {
        $rootLayer = $entity->getRootlayer();
        if ($rootLayer) {
            WmsLayerSourceEntityHandler::persistRecursive($manager, $rootLayer);
        }
        $manager->persist($entity);
        $cont = $entity->getContact();
        if ($cont == null) {
            $cont = new Contact();
            $entity->setContact($cont);
        }
        $manager->persist($cont);
        foreach ($entity->getKeywords() as $kwd) {
            $manager->persist($kwd);
        }
    }

    /**
     * Creates a new WmsInstance, optionally attaches it to a layerset, then updates
     * the ordering of the layers.
     *
     * @param Layerset|null $layerSet new instance will be attached to layerset if given
     * @return WmsInstance
     */
    public function createInstance(Layerset $layerSet = NULL)
    {
        $instance        = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->setLayerset($layerSet);
        $instance->populateFromSource($this->entity);
        if ($layerSet) {
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
     * @inheritdoc
     */
    public function remove()
    {
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    /**
     * Update a source from a new source
     *
     * @param WmsSource|Source $sourceNew
     */
    public function update(Source $sourceNew)
    {
        $em = $this->container->get('doctrine')->getManager();
        $transaction = $em->getConnection()->isTransactionActive();
        if (!$transaction) {
            $em->getConnection()->beginTransaction();
        }
//        $updater = new WmsUpdater($this->entity);
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
        $this->container->get('doctrine')->getManager()->detach($contact);
        if ($this->entity->getContact()) {
            $this->container->get('doctrine')->getManager()->remove($this->entity->getContact());
        }
        $this->entity->setContact($contact);

        $rootUpdateHandler = new WmsLayerSourceEntityHandler($this->container, $this->entity->getRootlayer());
        $rootUpdateHandler->update($sourceNew->getRootlayer());

        KeywordUpdater::updateKeywords(
            $this->entity,
            $sourceNew,
            $this->container->get('doctrine')->getManager(),
            'Mapbender\WmsBundle\Entity\WmsSourceKeyword'
        );

        foreach ($this->getInstances() as $instance) {
            $instanceUpdateHandler = new WmsInstanceEntityHandler($this->container, $instance);
            $instanceUpdateHandler->update();
        }

        if (!$transaction) {
            $this->container->get('doctrine')->getManager()->getConnection()->commit();
        }
    }

    /**
     * @return WmsInstance[]
     */
    public function getInstances()
    {
        /** @var Query $query */
        $objectManager = $this->container->get('doctrine')->getManager();
        $query         = $objectManager->createQuery("SELECT i FROM MapbenderWmsBundle:WmsInstance i WHERE i.source=:sid");
        return $query->setParameters(
            array("sid" => $this->entity->getId())
        )->getResult();
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
