<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Mapbender\CoreBundle\Entity\SRS;
use Mapbender\CoreBundle\Component\SrsParser;

class LoadSRSData implements FixtureInterface {

    public function load(ObjectManager $manager) {
        $srsParser = new SrsParser();
        $data = $srsParser->parseSrsData("/home/paul/Projects/mapbender-starter/application/app/Resourses/proj4/epsg", "EPSG");
        foreach ($data as $item) {
            $srs = new SRS();
            $srs->setName($item["name"]);
            $srs->setTitle($item["title"]);
            $srs->setDefinition($item["definition"]);

            $manager->persist($srs);
            $manager->flush();
        }
            
    }

}
