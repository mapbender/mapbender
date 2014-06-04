<?php

namespace Mapbender\CoreBundle\Tests\Controller;

class SeleniumPhantomJsTest extends \PHPUnit_Extensions_Selenium2TestCase {

    public function setUp() {
        if(PHP_MINOR_VERSION == 3) {
            $this->markTestIncomplete('This test does not run on PHP 5.3.');
            return;
        }
        try {
            $this->setHost('localhost');
            $this->setPort(9876);
            $this->setBrowserUrl('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_dev.php/');
        }catch(\Exception $e) {
            // skip test on PHP 5.3
            if(PHP_MINOR_VERSION == 3) {
                return;
            }
            throw $e;
        }
    }

    public function prepareSession() {
        try{
            $res = parent::prepareSession();
            $this->url('/');
            return $res;
        }catch(\Exception $e) {
            // skip test on PHP 5.3
            if(PHP_MINOR_VERSION == 3) {
                return;
            }
            throw $e;
        }
    }

    public function testIndex() {
        try {
            $this->url('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_dev.php/');
            $this->assertEquals('Applications', $this->title());
        }catch(\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            // skip test on PHP 5.3
            if(PHP_MINOR_VERSION == 3) {
                return;
            }
            throw $e;
        }
    }
}
