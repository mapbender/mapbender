<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\CoreBundle\Entity\Contact;

/**
 * Description of WmsSourceEntityHandler
 *
 * @author Paul Schmidt
 */
class WmsSourceEntityHandler extends SourceEntityHandler
{

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
        $manager = $this->container->get('doctrine')->getManager();
        if ($this->entity->getRootlayer()) {
            self::createHandler($this->container, $this->entity->getRootlayer())->save();
        }
        $manager->persist($this->entity);
        $cont = $this->entity->getContact();
        if($cont == null) {
            $cont = new Contact();
            $this->entity->setContact($cont);
        }
        $manager->persist($cont);
        foreach ($this->entity->getKeywords() as $kwd) {
            $manager->persist($kwd);
        }
    }

    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset = NULL)
    {
        $instance        = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->setLayerset($layerset);
        $instanceHandler = self::createHandler($this->container, $instance);
        $instanceHandler->create();
        if ($instanceHandler->getEntity()->getLayerset()) {
            $num = 0;
            foreach ($instanceHandler->getEntity()->getLayerset()->getInstances() as $instanceAtLayerset) {
                $instHandler = self::createHandler($this->container, $instanceAtLayerset);
                $instHandler->getEntity()->setWeight($num);
                $instHandler->generateConfiguration();
                $num++;
            }
        }
        $instanceHandler->generateConfiguration();
        return $instanceHandler->getEntity();
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        if ($this->entity->getRootlayer()) {
            self::createHandler($this->container, $this->entity->getRootlayer())->remove();
        }
        $this->container->get('doctrine')->getManager()->remove($this->entity->getContact());
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    /**
     * @inheritdoc
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
        $this->container->get('doctrine')->getManager()->remove($this->entity->getContact());
        $this->entity->setContact($contact);

        self::createHandler($this->container, $this->entity->getRootlayer())->update($sourceNew->getRootlayer());

        KeywordUpdater::updateKeywords(
            $this->entity,
            $sourceNew,
            $this->container->get('doctrine')->getManager(),
            'Mapbender\WmsBundle\Entity\WmsSourceKeyword'
        );

        foreach ($this->getInstances() as $instance) {
            self::createHandler($this->container, $instance)->update();
        }

        if (!$transaction) {
            $this->container->get('doctrine')->getManager()->getConnection()->commit();
        }
    }

    /**
     * @inheritdoc
     */
    public function getInstances()
    {
        $query    = $this->container->get('doctrine')->getManager()->createQuery(
            "SELECT i FROM MapbenderWmsBundle:WmsInstance i WHERE i.source=:sid"
        );
        $query->setParameters(array("sid" => $this->entity->getId()));
        $instList = $query->getResult();
        return $instList;
    }
}
