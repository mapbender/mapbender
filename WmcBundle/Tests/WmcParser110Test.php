<?php

namespace Mapbender\WmcBundle\Tests\Component;

use Mapbender\WmcBundle\Component\WmcParser110;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WmcParser110Test extends WebTestCase
{

    public function testMinimal(){
        $client = self::createClient();
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wmc_example.xml"));
        $doc = WmcParser110::createDocument($data);
        $parser  = WmcParser110::getParser($client->getContainer(), $doc);
        $wmc = $parser->parse();

    }

}
