<?php


namespace Mapbender\CoreBundle\EventHandler\InitDb;


use Doctrine\ORM\EntityManager;
use Mapbender\Component\Event\AbstractInitDbHandler;
use Mapbender\Component\Event\InitDbEvent;
use Mapbender\CoreBundle\Entity\SRS;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Wires Epsg database update into mapbender:database:init CLI.
 *
 * @todo v3.1: remove fixture, move logic here; fixtures are for unit tests, not for
 *             setup. Fixtures are also hard to rerun manually, unlike console commands.
 * TBD: may be more efficient to use an ObjectRepository processing the definition file
 * directly (read only, no database table).
 */
class UpdateEpsgHandler extends AbstractInitDbHandler
{
    /** @var EntityManager */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onInitDb(InitDbEvent $event)
    {
        $output = $event->getOutput();

        $filepath = __DIR__ . '/../../Resources/proj4/proj4js_epsg.txt';

        $output->writeln("Importing EPSG definitions from " . realpath($filepath));
        $file     = @fopen($filepath, "r");
        $repo = $this->entityManager->getRepository($class = get_class(new SRS()));
        $imported = 0;
        $updated  = 0;
        while (!feof($file)) {
            $help = trim(str_ireplace("\n", "", fgets($file)));
            if (strlen($help) === 0) {
                continue;
            }
            $temp = explode("|", $help);
            if ($temp[0] === null || strlen($temp[0]) === 0) {
                continue;
            }
            $srs = $repo->findOneBy(array('name' => $temp[0]));
            if ($srs) {
                $srs->setTitle($temp[1]);
                $srs->setDefinition($temp[2]);
                $updated++;
            } else {
                $srs = new SRS();
                $srs->setName($temp[0]);
                $srs->setTitle($temp[1]);
                $srs->setDefinition($temp[2]);
                $imported++;
            }
            $this->entityManager->persist($srs);
        }
        $this->entityManager->flush();

        fclose($file);
        $output->writeln("Updated {$updated} EPSG entities, created {$imported}", OutputInterface::VERBOSITY_VERBOSE);

    }
}
