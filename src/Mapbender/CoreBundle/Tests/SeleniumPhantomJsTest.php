<?php

namespace Mapbender\CoreBundle\Tests\Controller;

class SeleniumPhantomJsTest extends \PHPUnit_Extensions_Selenium2TestCase
{

    public function setUp()
    {
        if (PHP_MINOR_VERSION == 3) {
            $this->markTestIncomplete('This test does not run on PHP 5.3.');
            return;
        }
        $this->setHost('localhost');
        $this->setPort(9876);
        $this->setBrowserUrl('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT);
    }

    public function prepareSession()
    {
        $res = parent::prepareSession();
        $this->url('/');
        return $res;
    }

    public function testIndex()
    {
        $this->url('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT);
        $this->assertEquals('Applications', $this->title());
    }
}
