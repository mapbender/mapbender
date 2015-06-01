<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM\Application;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
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
            $id = $application->getId();

            $this->saveSources($manager, $application);
            $this->saveLayersets($manager, $application);
            $this->saveElements($manager, $application);
            $manager->persist($application->setUpdated(new \DateTime('now')));
            $this->checkRegionProperties($manager, $application);
            $manager->flush();
            $this->addMapping($application, $id, $application);
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

    private function saveSources(ObjectManager $manager, ApplicationEntity $application)
    {
        foreach ($application->getLayersets() as $layerset) {
            foreach ($layerset->getInstances() as $instance) {
                $instlayerOldIds = array();
                foreach ($instance->getLayers() as $instlayer) {
                    $instlayerOldIds[] = $instlayer->getId();
                }
                $source = $instance->getSource();
                $id = $source->getId();
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
                    EntityHandler::createHandler($this->container, $source)->save();
                    $num = 0;
                    foreach ($source->getLayers() as $layer) {
                        $this->addMapping($layer, $layerOldIds[$num], $layer);
                        $num++;
                    }
                    $this->addMapping($source, $id, $source);
                } else {
                    $source = $founded;
                    $num = 0;
                    foreach ($source->getLayers() as $layer) {
                        $this->addMapping($layer, $layerOldIds[$num], $layer);
                        $num++;
                    }
                    $this->addMapping($source, $id, $source);
                    $instance->setSource($source);
                    $num = 0;
                    foreach ($instance->getLayers() as $instlayer) {
                        $instlayer->setSourceItem(
                            $this->mapper[get_class($instlayer->getSourceItem())][$instlayer->getSourceItem()->getId()]
                        );
                        EntityHandler::createHandler($this->container, $instlayer)->save();
                        $this->addMapping($instlayer, $instlayerOldIds[$num], $instlayer);
                    }
                }
            }
        }
    }

    private function saveLayersets(ObjectManager $manager, ApplicationEntity $application)
    {
        foreach ($application->getLayersets() as $layerset) {
            $id = $layerset->getId();
            $manager->persist($layerset);
            $this->addMapping($layerset, $id, $layerset);
        }
    }

    private function saveElements(ObjectManager $manager, ApplicationEntity $application)
    {
        $elementIds = array();
        $num = 0;
        foreach ($application->getElements() as $element) {
//            $elementIds[] = $element->getId();
            $id = $element->getId();
            $manager->persist($element);
            $this->addMapping($element, $id, $element);
        }
        foreach ($application->getElements() as $element) {
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
