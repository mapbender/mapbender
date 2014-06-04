<?php

namespace Mapbender\CoreBundle\Tests\SeleniumIdeTests;

class LoginTest extends \PHPUnit_Extensions_Selenium2TestCase
{
  protected function setUp()
  {
      if(PHP_MINOR_VERSION == 3) {
          $this->markTestIncomplete('This test does not run on PHP 5.3.');
          return;
      }
      $this->setHost('localhost');
      $this->setPort(9876);
      $this->setBrowserUrl('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_dev.php/');
  }

  public function prepareSession() {
      $res = parent::prepareSession();
      $this->url('/');
      return $res;
  }

  public function testLoginAsRoot()
  {
    $test = $this; // Workaround for anonymous function scopes in PHP < v5.4.
    $session = $this->prepareSession(); // Make the session available.
    // get
    $this->url("http://localhost:8000/");
    // clickElement
    $this->byLinkText("Login")->click();
    // setElementText
    $element = $this->byId("username");
    $element->click();
    $element->clear();
    $element->value("root");
    // setElementText
    $element = $this->byId("password");
    $element->click();
    $element->clear();
    $element->value("root");
    // clickElement
    $this->byCssSelector("input.right.button")->click();
    // clickElement
    $this->byLinkText("New application")->click();
    // setElementText
    $element = $this->byId("application_title");
    $element->click();
    $element->clear();
    $element->value();
    /* $this->open("/"); */
    /* $this->click("//a[contains(@href, '/user/login')]"); */
    /* $this->waitForPageToLoad("3000"); */
    /* $this->type("id=username", "root"); */
    /* $this->type("id=password", "root"); */
    /* $this->click("css=input.right.button"); */
    /* $this->waitForPageToLoad("3000"); */
    /* for ($second = 0; ; $second++) { */
    /*     if ($second >= 60) $this->fail("timeout"); */
    /*     try { */
    /*         if ($this->isElementPresent("//\*[@id=\"accountMenu\"]")) break; */
    /*     } catch (Exception $e) {} */
    /*     sleep(1); */
    /* } */
  }
}
?>
