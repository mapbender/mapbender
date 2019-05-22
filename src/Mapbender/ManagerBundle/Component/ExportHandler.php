<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
class ExportHandler extends ExchangeHandler
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param EntityManager $entityManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(EntityManager $entityManager,
                                AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct($entityManager);
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param Application $application
     * @return array
     */
    public function exportApplication(Application $application)
    {
        gc_enable();
        $normalizer = new ExchangeNormalizer($this->em);
        $time = array(
            'start' => microtime(true)
        );
        $this->exportSources($application, $normalizer);
        $time['sources'] = microtime(true);
        $time['sources'] = $time['sources'] . '/' . ($time['sources'] - $time['start']);

        gc_collect_cycles();
        // export Application entity itself
        $normalizer->handleValue($application);
        gc_collect_cycles();
        $time['end'] = microtime(true);
        $time['total'] = $time['end'] - $time['start'];
        gc_collect_cycles();
        $export = $normalizer->getExport();
        $export['time'] = $time;
//        die(print_r($time,1));
        return $export;
    }

    /**
     * @param Application $application
     * @param $normalizer
     */
    private function exportSources(Application $application, ExchangeNormalizer $normalizer)
    {
        foreach ($this->getAllowedApplicationSources($application) as $src) {
            $normalizer->handleValue($src);
            gc_collect_cycles();
        }
    }

    /**
     * Get current user allowed application sources
     *
     * @param Application $app
     * @return Source[]|ArrayCollection
     */
    protected function getAllowedApplicationSources(Application $app)
    {
        $sources = new ArrayCollection();
        if ($this->authorizationChecker->isGranted('EDIT', $app)) {
            foreach ($app->getLayersets() as $layerSet) {
                foreach ($layerSet->getInstances() as $instance) {
                    $source = $instance->getSource();
                    if ($this->authorizationChecker->isGranted('EDIT', $source)) {
                        $sources->add($source);
                    }
                }
            }
        }
        return $sources;
    }
}
