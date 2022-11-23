<?php


namespace Mapbender\CoreBundle\EventHandler\InitDb;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\Component\Event\AbstractInitDbHandler;
use Mapbender\Component\Event\InitDbEvent;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Fixes incomplete Element "weight" column values that may have been broken
 * by buggy older / development versions.
 *
 * Hooked into `app/console mapbender:database:init`
 */
class FixElementWeightsHandler extends AbstractInitDbHandler
{
    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onInitDb(InitDbEvent $event)
    {
        $affected = $this->scanApplications();
        if ($affected) {
            $this->em->clear();
            $event->getOutput()->writeln("Element weights need fixing in " . count($affected) . " Applications" , OutputInterface::VERBOSITY_VERBOSE);
            foreach ($affected as $appInfo) {
                /** @var Application|null $application */
                $application = $this->em->getRepository(Application::class)->find($appInfo['id']);
                $this->em->persist($application);
                $this->processApplication($application, $appInfo['regions'], $event->getOutput());
                $application->setElements($application->getElements());
                $application->setUpdated(new \DateTime());
                $this->em->flush();
            }
        } else {
            $event->getOutput()->writeln("All application Element weights ok" , OutputInterface::VERBOSITY_VERBOSE);
        }
    }

    /**
     * @param Application $application
     * @param string[] $regionNames
     * @param OutputInterface $output
     */
    protected function processApplication(Application $application, $regionNames, OutputInterface $output)
    {
        $output->writeln("Fixing element weights in Application {$application->getSlug()}", OutputInterface::VERBOSITY_NORMAL);
        $allElements = $application->getElements()->matching(Criteria::create()->orderBy(array(
            'region' => Criteria::ASC,
            'weight' => Criteria::ASC,
            'id' => Criteria::ASC,  // If all else fails...
        )));
        foreach ($regionNames as $regionName) {
            $partitions = $allElements->partition(function($_, $element) use ($regionName) {
                /** @var Element $element */
                return $element->getRegion() === $regionName;
            });
            WeightSortedCollectionUtil::reassignWeights($partitions[0]);
        }
    }

    /**
     * Returns set of Application ids and region names where element weights need fixing
     * @return array
     */
    protected function scanApplications()
    {
        $connection = $this->em->getConnection();
        $tn = $this->em->getClassMetadata(Element::class)->getTableName();
        $scanSql = 'SELECT application_id, region'
                 . ', COUNT(DISTINCT weight) AS c0, COUNT(*) AS c1'
                 . ', MIN(weight) AS weight0, MAX(weight) AS weight1'
                 . ' FROM ' . $connection->quoteIdentifier($tn)
                 . ' GROUP BY application_id, region'
        ;
        $results = $connection->fetchAllAssociative($scanSql);
        $applicationMap = array();
        foreach ($results as $row) {
            $needReorder = $row['c1'] != $row['c0'];
            $needReorder = $needReorder || ($row['weight0'] < 0);
            $needReorder = $needReorder || ($row['weight1'] != ($row['c1'] - 1));
            if ($needReorder) {
                $id = $row['application_id'];
                if (empty($applicationMap[$id])) {
                    $applicationMap[$id] = array(
                        'id' => $id,
                        'regions' => array(),
                    );
                }
                $applicationMap[$id]['regions'][] = $row['region'];
            }
        }
        return array_values($applicationMap);
    }
}
