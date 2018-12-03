<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\ManagerBundle\Form\Type\ExportJobType;
use Symfony\Component\Form\FormFactoryInterface;
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
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(EntityManager $entityManager,
                                AuthorizationCheckerInterface $authorizationChecker,
                                FormFactoryInterface $formFactory)
    {
        parent::__construct($entityManager, $formFactory);
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @inheritdoc
     */
    public function createForm()
    {
        $allowedApps = $this->getAllowedApplications();
        $type = new ExportJobType();
        $data = new ExportJob();
        return $this->formFactory->create($type, $data, array('application' => $allowedApps));
    }

    /**
     * Get current user allowed applications
     *
     * @return Application[]
     */
    protected function getAllowedApplications()
    {
        $repository = $this->em->getRepository('Mapbender\CoreBundle\Entity\Application');
        $allowedApps = array();
        foreach ($repository->findAll() as $application) {
            /** @var Application $application */
            if ($this->authorizationChecker->isGranted('EDIT', $application)) {
                $allowedApps[] = $application;
            }
        }
        return $allowedApps;
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
