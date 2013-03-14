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
        $filepath = __DIR__ . '/../../../Resources/proj4/proj4js_epsg.txt';
        $file = @fopen($filepath, "r");
        while(!feof($file))
        {
            $help = trim(str_ireplace("\n", "", fgets($file)));
            if(strlen($help) === 0)
            {
                continue;
            }
            $temp = explode("|", $help);
            if($temp[0] === null || strlen($temp[0]) === 0)
            {
                continue;
            }
            $srs = new SRS();
            $srs->setName($temp[0]);
            $srs->setTitle($temp[1]);
            $srs->setDefinition($temp[2]);

            $manager->persist($srs);
        }
        $manager->flush();
        fclose($file);
    }

}
