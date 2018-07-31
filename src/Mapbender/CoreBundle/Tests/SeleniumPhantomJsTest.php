<?php

namespace Mapbender\CoreBundle\Tests\Controller;

/**
 * @group functional
 */
class SeleniumPhantomJsTest extends \PHPUnit_Extensions_Selenium2TestCase
{

    public function setUp()
    {
        if (!version_compare(PHP_VERSION, '5.4', '>=')) {
            $this->markTestSkipped('This test requires PHP >= 5.4');
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
