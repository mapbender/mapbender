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

  public function loginRoot()
  {
    $test = $this; // Workaround for anonymous function scopes in PHP < v5.4.
    $session = $this->prepareSession(); // Make the session available.
    // get
    $this->url('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_dev.php/');
    // waitForTextPresent
    $this->waitUntil(function() use ($test) {
      try {
        $boolean = (strpos($test->byTag('html')->text(), "Login") !== false);
      } catch (\Exception $e) {
        $boolean = false;
      }
      return $boolean === true ?: null;
    });
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
    // waitForElementPresent
    $this->waitUntil(function() use ($test) {
      try {
        $boolean = ($test->byId("accountOpen") instanceof \PHPUnit_Extensions_Selenium2TestCase_Element);
      } catch (\Exception $e) {
        $boolean = false;
      }
      return $boolean === true ?: null;
    });
  }

  public function logoutRoot() {
    $test = $this; // Workaround for anonymous function scopes in PHP < v5.4.
    $this->waitUntil(function() use ($test) {
      try {
        $boolean = (strpos($test->byLinkText('Logout')->text(), "Logout") !== false);
      } catch (\Exception $e) {
        $boolean = false;
      }
      return $boolean === true ?: null;
    });
    $this->byLinkText("Logout")->click();
  }

  public function testLoginLogoutRoot() {
    $this->loginRoot();
    $this->logoutRoot();
  }

}
?>
