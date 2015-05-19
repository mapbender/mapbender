<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM\Application;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Mapbender\CoreBundle\Component\Element as ElementComponent;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;

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
            $appMapper = new \Mapbender\CoreBundle\Component\ApplicationYAMLMapper($this->container);
            $application = $appMapper->getApplication($slug);
            if ($application->getLayersets()->count() === 0) {
                continue;
            }
            $manager->getConnection()->beginTransaction();
            $application->setSource(ApplicationEntity::SOURCE_DB);
            $id = $application->getId();

            $this->saveSources($manager, $application);
            $this->saveLayersets($manager, $application);
            $this->saveElements($manager, $application);
//            $application->setId(null);
            $manager->persist($application->setUpdated(new \DateTime('now')));
            $manager->flush();
            $this->addMapping($application, $id, $application);
            $manager->getConnection()->commit();
        }
    }
    
    private function addMapping($object, $id_old, $objWithNewId)
    {
        $class = is_object($object) ? get_class($object) : $object;
        if (!isset($this->mapper[$class])) {
            $this->mapper[$class] = array();
        }
        $this->mapper[$class][$id_old] = $objWithNewId;
    }

    private function saveSources(ObjectManager $manager, ApplicationEntity $application)
    {
        foreach ($application->getLayersets() as $layerset) {
            foreach ($layerset->getInstances() as $instance) {
                $source = $instance->getSource();
                $id = $source->getId();
                $layerOldIds = array();
                foreach ($source->getLayers() as $layer) {
                    $layerOldIds[] = $layer->getId();
                }
                $repo = $manager->getRepository(get_class($source));
//                $meta = $manager->getClassMetadata(get_class($source));
////                $a = $meta->__toString();
//                $apps = $repo->findBy(array(
//                    "originUrl" => $source->getOriginUrl()
//                ));
                $founded = null;
                foreach ($repo->findBy(array('originUrl' => $source->getOriginUrl())) as $fsource) {
                    if ($source->getLayers()->count() !== $fsource->getLayers()->count()) {
                        $founded = $fsource;
                        break;
                    }
                }
                if (!$founded) {
                    $contact = new Contact();
                    $contact->setPerson(null)
                        ->setOrganization(null)
                        ->setPosition(null)
                        ->setAddressType(null)
                        ->setAddress(null)
                        ->setAddressCity(null)
                        ->setAddressStateOrProvince(null)
                        ->setAddressPostCode(null)
                        ->setAddressCountry(null)
                        ->setVoiceTelephone(null)
                        ->setFacsimileTelephone(null)
                        ->setElectronicMailAddress(null);
                    $source->setContact($contact);
                    EntityHandler::createHandler($this->container, $source)->save();
//                    $manager->flush();
                } else {
                    $source = $founded;
                }
                $num = 0;
                foreach ($source->getLayers() as $layer) {
                    $this->addMapping($layer, $layerOldIds[$num], $layer);
                    $num++;
                }
                $this->addMapping($source, $id, $source);
            }
//            $id = $layerset->getId();
//            $layerset->setId(null);
//            $manager->persist($layerset);
//            $this->addMapping($layerset, $id, $layerset->getId());
        }
    }

    private function saveLayersets(ObjectManager $manager, ApplicationEntity $application)
    {
        foreach ($application->getLayersets() as $layerset) {
            $id = $layerset->getId();
            $instOldIds = array();
            foreach ($layerset->getInstances() as $instance) {
                $instOldIds[] = $instance->getId();
            }
            foreach ($layerset->getInstances() as $instance) {
                $this->saveInstance($manager, $layerset, $instance);
//                $manager->flush();
            }
//            $layerset->setId(null);
            $manager->persist($layerset);
//            $manager->flush();
            $this->addMapping($layerset, $id, $layerset);
            $num = 0;
            foreach ($layerset->getInstances() as $instance) {
                $this->addMapping($layerset, $instOldIds[$num], $instance);
//                EntityHandler::createHandler($this->container, $instance)->save();
                $num++;
            }
        }
    }

    private function saveInstance(ObjectManager $manager, Layerset $layerset, SourceInstance $instance)
    {
//        $idInst =  $instance->getId();
//        $in_id = $instance->getId();
//        $instance->getSource()->setId(null);
//        EntityHandler::createHandler($this->container, $instance->getSource())->save();
//        $this->addMapping($instance->getSource(), $id, $instance->getSource()->getId());
//        $instance->getSource()->setId(null);
        foreach ($instance->getLayers() as $layer) {
            $idLayInst =  $layer->getId();
//            $layer->setId(null);
            if (!is_int($layer->getSourceItem()->getId())) {
                $class = get_class($layer->getSourceItem());
                $layer->setSourceItem($this->mapper[$class][$layer->getSourceItem()->getId()]);
            }
            EntityHandler::createHandler($this->container, $layer)->save();
//            $manager->flush();
            $this->addMapping($layer, $idLayInst, $layer);
//
//            $idLay =  $layer->getSourceItem()->getId();
//            $layer->getSourceItem()->setId(null);
//            EntityHandler::createHandler($this->container, $layer->getSourceItem())->save();
//            $this->addMapping($layer->getSourceItem(), $idLay, $layer->getSourceItem()->getId());
        }
//        $instance->setId(null);
//        EntityHandler::createHandler($this->container, $instance)->save();
//        $this->addMapping($instance, $idInst, $instance);
    }

    private function saveElements(ObjectManager $manager, ApplicationEntity $application)
    {
        foreach ($application->getElements() as $element) {
            $id = $element->getId();
//            $element->setId(null);
            $manager->persist($element);
//            $manager->flush();
            $this->addMapping($element, $id, $element);
        }
    }



    /**
     * @inheritdoc
     */
    public function loadOld(ObjectManager $manager)
    {
        $definitions = $this->container->getParameter('applications');
        $manager->getConnection()->beginTransaction();
        foreach ($definitions as $slug => $definition) {
            if (isset($definition['excludeFromList']) && $definition['excludeFromList']) {
                continue;
            }
            $timestamp = round((microtime(true) * 1000));
            if (!key_exists('title', $definition)) {
                $definition['title'] = "TITLE " . $timestamp;
            }

            if (!key_exists('published', $definition)) {
                $definition['published'] = false;
            } else {
                $definition['published'] = (boolean) $definition['published'];
            }
            // First, create an application entity
            $application = new ApplicationEntity();
            $application
                ->setSlug($timestamp . "_" . $slug)
                ->setTitle($timestamp . " " . (isset($definition['title']) ? $definition['title'] : ''))
                ->setDescription(isset($definition['description']) ? $definition['description'] : '')
                ->setTemplate($definition['template'])
                ->setPublished(isset($definition['published']) ? $definition['published'] : false)
                ->setUpdated(new \DateTime('now'));
            if (array_key_exists('extra_assets', $definition)) {
                $application->setExtraAssets($definition['extra_assets']);
            }

            $application->yaml_roles = array();
            if (array_key_exists('roles', $definition)) {
                $application->yaml_roles = $definition['roles'];
            }
            $manager->persist($application);
            $layersets_map = array();
            foreach ($definition['layersets'] as $layersetName => $layersetDef) {
                $layerset = new Layerset();
                $layerset->setTitle($layersetName);
                $layerset->setApplication($application);
                $manager->persist($layerset);
                $application->addLayerset($layerset);
                $manager->flush();
                $layersets_map[$layersetName] = $layerset->getId();
            }
            $manager->persist($application);

            // Set inital ACL
            $aces = array();
            $aces[] = array(
                'sid' => new RoleSecurityIdentity('IS_AUTHENTICATED_ANONYMOUSLY'),
                'mask' => MaskBuilder::MASK_VIEW);

            $aclManager = $this->container->get('fom.acl.manager');
            $aclManager->setObjectACL($application, $aces, 'object');

            $elements_map = array();
            // Then create elements
            foreach ($definition['elements'] as $region => $elementsDefinition) {
                if ($elementsDefinition !== null) {
                    $weight = 0;
                    foreach ($elementsDefinition as $element_yml_id => $elementDefinition) {
                        $class = $elementDefinition['class'];
                        $title = array_key_exists('title', $elementDefinition)
                            && $elementDefinition['title'] !== null ?
                            $elementDefinition['title'] :
                            $class::getClassTitle();

                        $element = new Element();

                        $element->setClass($elementDefinition['class'])
                            ->setTitle($title)
                            ->setConfiguration($elementDefinition)
                            ->setRegion($region)
                            ->setWeight($weight++)
                            ->setApplication($application);
                        //TODO: Roles
                        $application->addElements($element);
                        $manager->persist($element);
                        $manager->flush();
                        $elements_map[$element_yml_id] = $element->getId();
                    }
                }
            }
            // Then merge default configuration and elements configuration
            foreach ($application->getElements() as $element) {
                $configuration_yml = $element->getConfiguration();
                $entity_class = $configuration_yml['class'];
                $appl = new \Mapbender\CoreBundle\Component\Application($this->container, $application, array());
                $elComp = new $entity_class($appl, $this->container, new Element());
                unset($configuration_yml['class']);
                unset($configuration_yml['title']);

                $configuration =
                    ElementComponent::mergeArrays($elComp->getDefaultConfiguration(), $configuration_yml, array());

                if (key_exists("target", $configuration)
                    && $configuration["target"] !== null
                    && key_exists($configuration["target"], $elements_map)) {
                    $configuration["target"] = $elements_map[$configuration["target"]];
                }
                if (key_exists("layerset", $configuration_yml)
                    && $configuration["layerset"] !== null
                    && key_exists($configuration["layerset"], $layersets_map)) {
                    $configuration["layerset"] = $layersets_map[$configuration["layerset"]];
                }

                $class = $elementDefinition['class'];
                $title = array_key_exists('title', $elementDefinition) ?
                    $elementDefinition['title'] :
                    $class::getClassTitle();
                $element->setConfiguration($configuration);
                $manager->persist($element);
            }
            $manager->flush();
            $ccc;
        }
        $manager->getConnection()->commit();
    }
}
