<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM\Epsg;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Mapbender\CoreBundle\Entity\SRS;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copies epsg code definitions into the database.
 * Not a fixture.
 *
 * @author Paul Schmidt
 */
class LoadEpsgData implements FixtureInterface
{
    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        $output = new ConsoleOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $output->writeln("<error>Invoking Epsg update as a fixture is deprecated. Use mapbender:database:init command.</error>");

        $this->doLoad($manager, $output);
    }

    public static function doLoad(ObjectManager $manager, OutputInterface $output)
    {
        $filepath = __DIR__ . '/../../../Resources/proj4/proj4js_epsg.txt';
        $output->writeln("Importing EPSG definitions from " . realpath($filepath));
        $file     = @fopen($filepath, "r");
        $repo = $manager->getRepository($class = get_class(new SRS()));
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
            $manager->persist($srs);
        }
        $manager->flush();

        fclose($file);
        $output->writeln("Updated {$updated} EPSG entities, created {$imported}", OutputInterface::VERBOSITY_VERBOSE);
    }
}
