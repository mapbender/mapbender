<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Util\ClassUtils;
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
        $this->updateSource($this->entity, $sourceNew);
    }

    public function updateSource(WmsSource $target, WmsSource $sourceNew)
    {
        $em = $this->getEntityManager();
        $transaction = $em->getConnection()->isTransactionActive();
        if (!$transaction) {
            $em->getConnection()->beginTransaction();
        }
        $classMeta = $em->getClassMetadata(ClassUtils::getClass($target));
        EntityUtil::copyEntityFields($target, $sourceNew, $classMeta, false);

        $contact = clone $sourceNew->getContact();
        $em->detach($contact);
        if ($target->getContact()) {
            $em->remove($target->getContact());
        }
        $target->setContact($contact);

        $rootUpdateHandler = new WmsLayerSourceEntityHandler($this->container, $target->getRootlayer());
        $rootUpdateHandler->updateLayer($target->getRootlayer(), $sourceNew->getRootlayer());

        KeywordUpdater::updateKeywords(
            $target,
            $sourceNew,
            $em,
            'Mapbender\WmsBundle\Entity\WmsSourceKeyword'
        );

        $instanceUpdateHandler = new WmsInstanceEntityHandler($this->container, null);
        foreach ($target->getInstances() as $instance) {
            $instanceUpdateHandler->updateInstance($instance);
        }

        if (!$transaction) {
            $em->getConnection()->commit();
        }
    }
}
