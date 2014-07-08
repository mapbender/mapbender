<?php

namespace Mapbender\CoreBundle\Tests\Controller;

use FOM\Component\Test\SharedApplicationWebTestCase;

class ApplicationControllerTest extends SharedApplicationWebTestCase
{
    public function testIndex() {
        $crawler = self::$client->request('GET', '/application/mapbender_user');
        $this->assertTrue(self::$client->getResponse()->isSuccessful());
    }
}
