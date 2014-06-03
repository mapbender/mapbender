<?php

namespace Mapbender\CoreBundle\Tests\Controller;

use FOM\Component\Test\SharedApplicationWebTestCase;

class SeleniumPhantomJsTest extends \PHPUnit_Extensions_Selenium2TestCase {

    public function setUp() {
        $this->setHost('localhost');
        $this->setPort(9876);
        $this->setBrowserUrl('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_test.php/');
    }

    public function prepareSession() {
        $res = parent::prepareSession();
        $this->url('/');
        return $res;
    }

    public function testIndex() {
        $this->url('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_test.php/');
        $this->assertEquals('Applications', $this->title());
    }
}
