<?php

namespace Mapbender\CoreBundle\Tests\SeleniumIdeTests;

class LoginTest extends \PHPUnit_Extensions_SeleniumTestCase
{
  protected function setUp()
  {
      $this->setHost('localhost');
      $this->setPort(4445);
      $this->setBrowser("*chrome");
      $this->getDriver(array('name' => 'chrome'))->setWebDriverCapabilities(array('tunnel-identifier' => getenv('TRAVIS_JOB_NUMBER')));
      $this->setBrowserUrl('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_dev.php/');
  }

  public function testMyTestCase()
  {
    $this->open("/");
    $this->click("//a[contains(@href, '/user/login')]");
    $this->waitForPageToLoad("3000");
    $this->type("id=username", "root");
    $this->type("id=password", "foobar123");
    $this->click("css=input.right.button");
    $this->waitForPageToLoad("3000");
  }
}
?>