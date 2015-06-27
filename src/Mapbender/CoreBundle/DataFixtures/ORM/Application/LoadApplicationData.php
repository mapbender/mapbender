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

    private $container;

    public function setContainer(ContainerInterface $container = NULL)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager) {
        $definitions = $this->container->getParameter('applications');
        $sourceLays = array();
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
            foreach($application->getRegionProperties() as $prop) {
                $manager->persist($prop);
            }
            foreach($application->getElements() as $elm) {
                $elms[$elm->getId()] = $elm;
                $manager->persist($elm);
            }
            $this->persistLayersets($manager, $lays, $application->getLayersets(), $sourceLays);
            $manager->flush();
            foreach($application->getRegionProperties() as $prop) {
                $prop->setApplication($application);
                $manager->persist($prop);
            }
            $this->updateElements($elms, $lays, $manager);
            $manager->flush();
            $manager->getConnection()->commit();
            $appHandler->createAppWebDir($this->container, $application->getSlug());
        }
    }

    private function persistLayersets($manager, &$lays, $sets, &$sourceLays) {
        foreach($sets as $set) {
            $lays[$set->getId()] = $set;
            $manager->persist($set);
            foreach($set->getInstances() as $inst) {
                $src = $inst->getSource();
                $srcId = $src->getId();
                $matching = $this->findMatchingSource($manager, $src);
                if($matching == null || !array_key_exists($srcId, $sourceLays)) {
                    $sourceLays[$srcId] = array();
                    $matching = null;
                } else {
                    $inst->setSource($matching);
                    $src = $matching;
                }
                foreach($src->getLayers() as $lay) {
                    $manager->persist($lay);
                }
                $manager->persist($src);
                foreach($inst->getLayers() as $lay) {
                    if($matching != null) {
                        $lay->setSourceItem($sourceLays[$srcId][$lay->getId()]->getSourceItem());
                    } else {
                        $sourceLays[$srcId][$lay->getId()] = $lay;
                    }
                    $manager->persist($lay);
                }
                $manager->persist($inst);
            }
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

}
