<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM\Application;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Utils\EntityUtil;

/**
 * The class LoadApplicationData loads the applications from the "mapbender.yml"
 * into a mapbender database.
 *
 * @author Paul Schmidt
 */
class LoadApplicationData implements FixtureInterface, ContainerAwareInterface
{

    /**
     * Container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var array mapper old id- new id.
     */
    private $mapper = array();

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = NULL)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager) {
        $definitions = $this->container->getParameter('applications');
        foreach ($definitions as $slug => $definition) {
            $appMapper = new ApplicationYAMLMapper($this->container);
            $application = $appMapper->getApplication($slug);
            if ($application->getLayersets()->count() === 0) {
                continue;
            }
            $this->mapper = array();
            $appHandler = new Application($this->container, $application, array());

            $application->setSlug(
                EntityUtil::getUniqueValue($manager, get_class($application), 'slug', $application->getSlug() . '_yml', '')
            );
            $application->setTitle(
                EntityUtil::getUniqueValue($manager, get_class($application), 'title', $application->getSlug() . ' YML', '')
            );
            $manager->getConnection()->beginTransaction();
            $application->setSource(ApplicationEntity::SOURCE_DB);
            $manager->persist($application->setUpdated(new \DateTime('now')));
            $elms = array();
            $lays = array();
            $items = array();
            foreach($application->getRegionProperties() as $prop) {
                $manager->persist($prop);
            }
            foreach($application->getElements() as $elm) {
                $elms[$elm->getId()] = $elm;
                $manager->persist($elm);
            }
            foreach($application->getLayersets() as $set) {
                $lays[$set->getId()] = $set;
                $manager->persist($set);
                foreach($set->getInstances() as $inst) {
                    foreach($inst->getSource()->getLayers() as $lay) {
                        $manager->persist($lay);
                    }
                    $manager->persist($inst->getSource());
                    foreach($inst->getLayers() as $lay) {
                        $manager->persist($lay);
                    }
                    $manager->persist($inst);
                }
            }
            $manager->flush();
            $this->updateElements($elms, $lays, $manager);
            $manager->getConnection()->commit();
            $appHandler->createAppWebDir($this->container, $application->getSlug());
        }
    }

    private function updateElements(&$elms, &$lays, $manager) {
        foreach ($elms as $element) {
            $config = $element->getConfiguration();
            if (isset($config['target'])) {
                $elm = $elms[$config['target']];
                $config['target'] = $elm->getId();
                $element->setConfiguration($config);
                $manager->persist($element);
            }
            if (isset($config['layersets'])) {
                $layersets = array();
                foreach ($config['layersets'] as $layerset) {
                    $layerset = $lays[$layerset];
                    $layersets[] = $layerset->getId();
                }
                $config['layersets'] = $layersets;
                $element->setConfiguration($config);
                $manager->persist($element);
            }
            if (isset($config['layerset'])) {
                $layerset = $lays[$config['layerset']];
                $config['layerset'] = $layerset->getId();
                $element->setConfiguration($config);
                $manager->persist($element);
            }
        }
    }

    private function findMatchingSource($manager, $source) {
        $repo = $manager->getRepository(get_class($source));
        foreach ($repo->findBy(array('originUrl' => $source->getOriginUrl())) as $fsource) {
            if ($source->getLayers()->count() === $fsource->getLayers()->count()) {
                $ok = true;
                for ($i = 0; $i <  $source->getLayers()->count(); $i++) {
                    if ($source->getLayers()->get($i)->getName() !== $source->getLayers()->get($i)->getName()) {
                        $ok = false;
                    }
                }
                if ($ok) {
                    return $fsource;
                    break;
                }
            }
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function load2(ObjectManager $manager)
    {
        $definitions = $this->container->getParameter('applications');
        foreach ($definitions as $slug => $definition) {
            $appMapper = new ApplicationYAMLMapper($this->container);
            $application = $appMapper->getApplication($slug);
            if ($application->getLayersets()->count() === 0) {
                continue;
            }
            $this->mapper = array();
            $appHandler = new Application($this->container, $application, array());

            $application->setSlug(
                EntityUtil::getUniqueValue($manager, get_class($application), 'slug', $application->getSlug() . '_yml', '')
            );
            $application->setTitle(
                EntityUtil::getUniqueValue($manager, get_class($application), 'title', $application->getSlug() . ' YML', '')
            );
            $manager->getConnection()->beginTransaction();
            $application->setSource(ApplicationEntity::SOURCE_DB);
            $layersets = $application->getLayersets();
            $elements = $application->getElements();
            $regions = $application->getRegionProperties();
            $application->setLayersets(new ArrayCollection());
            $application->setElements(new ArrayCollection());
            $application->setRegionProperties(new ArrayCollection());
            $id = $application->getId();
            $manager->persist($application->setUpdated(new \DateTime('now')));

            $this->saveSources($manager, $layersets);
            $application->setLayersets($layersets);
            $manager->persist($application);
            $this->saveElements($manager, $elements);
            $application->setElements($elements);
            $this->checkRegionProperties($manager, $application);
            $manager->flush();
            $manager->getConnection()->commit();
            $appHandler->createAppWebDir($this->container, $application->getSlug());
        }
    }

    private function addMapping($object, $id_old, $objWithNewId, $unique = true)
    {
        $class = is_object($object) ? get_class($object) : $object;
        if (!isset($this->mapper[$class])) {
            $this->mapper[$class] = array();
        }
        if (!$unique) {
            $this->mapper[$class][$id_old] = $objWithNewId;
        } elseif (!isset($this->mapper[$class][$id_old])) {
            $this->mapper[$class][$id_old] = $objWithNewId;
        }
    }

    private function saveSources(ObjectManager $manager, &$layersets)
    {
        foreach ($layersets as $layerset) {
            $this->addMapping($layerset, $layerset->getId(), $layerset);
            foreach ($layerset->getInstances() as $instance) {
                $instlayerOldIds = array();
                foreach ($instance->getLayers() as $instlayer) {
                    $instlayerOldIds[] = $instlayer->getId();
                    foreach($instlayer->getSourceInstance()->getLayerset()->getInstances() as $in) {
                        $manager->persist($in);
                    }
                    $manager->persist($instlayer->getSourceInstance()->getLayerset());
                    $manager->persist($instlayer);
                }
                $source = $instance->getSource();
                $id = $source->getId();
                $manager->persist($source);
                $layerOldIds = array();
                foreach ($source->getLayers() as $layer) {
                    $layerOldIds[] = $layer->getId();
                }
                $founded = null;
                $repo = $manager->getRepository(get_class($source));
                foreach ($repo->findBy(array('originUrl' => $source->getOriginUrl())) as $fsource) {
                    if ($source->getLayers()->count() === $fsource->getLayers()->count()) {
                        $ok = true;
                        for ($i = 0; $i <  $source->getLayers()->count(); $i++) {
                            if ($source->getLayers()->get($i)->getName() !== $source->getLayers()->get($i)->getName()) {
                                $ok = false;
                            }
                        }
                        if ($ok) {
                            $founded = $fsource;
                            break;
                        }
                    }
                }
                if (!$founded) {
                    $source->setContact(new Contact());
                    $manager->persist($source->getContact());
                    $num = 0;
                    foreach ($source->getLayers() as $layer) {
                        $manager->persist($layer);
                        $this->addMapping($layer, $layerOldIds[$num], $layer);
                        $num++;
                    }
                    $this->addMapping($source, $id, $source);
                } else {
                    foreach ($instance->getLayers() as $instlayer) {
                        $item = $instlayer->getSourceItem();
                        $itemid = $item->getId();
                        $this->addMapping($item, $itemid, $item);
                        $manager->persist($item);
                    }
                }
            }
        }
    }

    private function saveElements2(ObjectManager $manager, &$elements)
    {
        $num = 0;
        foreach ($elements as $element) {
            $id = $element->getId();
            $manager->persist($element);
            $this->addMapping($element, $id, $element);
        }
        foreach ($elements as $element) {
            $id = $element->getId();
            $config = $element->getConfiguration();
            if (isset($config['target'])) {
                $elm = $this->mapper[get_class($element)][$config['target']];
                $config['target'] = $elm->getId();
                $element->setConfiguration($config);
                $manager->persist($element);
            }
            if (isset($config['layersets'])) {
                $layersets = array();
                foreach ($config['layersets'] as $layerset) {
                    $layerset = $this->mapper['Mapbender\CoreBundle\Entity\Layerset'][$layerset];
                    $layersets[] = $layerset->getId();
                }
                $config['layersets'] = $layersets;
                $element->setConfiguration($config);
                $manager->persist($element);
            }
            if (isset($config['layerset'])) {
                $layerset = $this->mapper['Mapbender\CoreBundle\Entity\Layerset'][$config['layerset']];
                $config['layerset'] = $layerset->getId();
                $element->setConfiguration($config);
                $manager->persist($element);
            }
        }
    }

    private function checkRegionProperties(ObjectManager $manager, ApplicationEntity $application)
    {
        $templateClass = $application->getTemplate();
        $templateProps = $templateClass::getRegionsProperties();
        // add RegionProperties if defined
        foreach ($templateProps as $regionName => $regionProps) {
            $exists = false;
            foreach ($application->getRegionProperties() as $regprops) {
                if ($regprops->getName() === $regionName) {
                    $regprops->setApplication($application);
                    $manager->persist($regprops);
                    $manager->persist($application);
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $regionProperties = new RegionProperties();
                $application->addRegionProperties($regionProperties);
                $regionProperties->setApplication($application);
                $regionProperties->setName($regionName);
                $manager->persist($regionProperties);
                $manager->persist($application);
            }
        }
    }
}
