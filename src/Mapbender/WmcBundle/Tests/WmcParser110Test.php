<?php

namespace Mapbender\WmcBundle\Tests\Component;

use Mapbender\WmcBundle\Component\WmcParser110;

class WmcParser110Test extends \PHPUnit_Framework_TestCase
{

    public function testMinimal(){
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wmc_example.xml"));
        $doc = WmcParser110::createDocument($data);
        $parser  = WmcParser110::getParser($doc);
        $wmc = $parser->parse();

    }

}
