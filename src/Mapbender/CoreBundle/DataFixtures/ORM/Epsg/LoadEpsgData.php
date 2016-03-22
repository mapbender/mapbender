<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Mapbender\CoreBundle\Entity\SRS;

/**
 * The LoadEpsgData loads the epsg parameter from a text file into a database table.
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
        $filepath = __DIR__.'/../../../Resources/proj4/proj4js_epsg.txt';
        $file     = @fopen($filepath, "r");
        $repo     = $manager->getRepository($class    = get_class(new SRS()));
        $imported = 0;
        $updated = 0;
        echo "EPSG ";
        while (!feof($file)) {
            $help = trim(str_ireplace("\n", "", fgets($file)));
            if (strlen($help) === 0) {
                continue;
            }
            $temp = explode("|", $help);
            if ($temp[0] === null || strlen($temp[0]) === 0) {
                continue;
            }
            if ($srs = $repo->findOneByName($temp[0])) {
                $srs->setTitle($temp[1]);
                $srs->setDefinition($temp[2]);
                $updated++;
            } else {
                $srs->setName($temp[0]);
                $srs->setTitle($temp[1]);
                $srs->setDefinition($temp[2]);
                $imported++;
            }
            $manager->persist($srs);
        }
        $manager->flush();
        echo "updated: " . $updated . ", imported: " . $imported . PHP_EOL;
        fclose($file);
    }
}