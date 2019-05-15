<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsSource;

/**
 * Description of WmtsSourceEntityHandler
 *
 * @property WmtsSource $entity
 *
 * @author Paul Schmidt
 */
class WmtsSourceEntityHandler extends SourceEntityHandler
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
        $entityManager = $this->getEntityManager();
        $entityManager->persist($this->entity);
        $cont = $this->entity->getContact();
        if ($cont == null) {
            $cont = new Contact();
            $this->entity->setContact($cont);
        }
        $entityManager->persist($cont);
        foreach ($this->entity->getLayers() as $layer) {
            $entityManager->persist($layer);
        }
        foreach ($this->entity->getThemes() as $theme) {
            $themeHandler = new ThemeEntityHandler($this->container, $theme);
            $themeHandler->save();
        }
        foreach ($this->entity->getTilematrixsets() as $tms) {
            $entityManager->persist($tms);
        }
        $entityManager->persist($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset = NULL)
    {
        $instance = new WmtsInstance();
        $instance->setSource($this->entity);
        $instanceHandler = new WmtsInstanceEntityHandler($this->container, $instance);
        $instanceHandler->create();
        if ($layerset) {
            $instance->setLayerset($layerset);
            $num = 0;
            foreach ($layerset->getInstances() as $instanceAtLayerset) {
                /** @var WmsInstance|WmtsInstance $instanceAtLayerset */
                $instanceAtLayerset->setWeight($num);
                $num++;
            }
        }
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getThemes() as $theme) {
            $themeHandler = new ThemeEntityHandler($this->container, $theme);
            $themeHandler->remove();
        }
        $this->getEntityManager()->remove($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function update(Source $sourceNew)
    {
    }

}
