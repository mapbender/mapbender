<?php

namespace Mapbender\CoreBundle\Tests\SeleniumIdeTests;

class AddAndRemoveUserTestTest extends \PHPUnit_Extensions_Selenium2TestCase
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
  
  /**
   * Recorded steps.
   */
  public function testAddAndRemoveUserTest() {
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
    // clickElement
    $this->byCssSelector("h1.contentTitle")->click();
    // clickElement
    $this->byLinkText("New user")->click();
    // setElementText
    $element = $this->byId("user_username");
    $element->click();
    $element->clear();
    $element->value("test");
    // setElementText
    $element = $this->byId("user_email");
    $element->click();
    $element->clear();
    $element->value("testing@example.com");
    // setElementText
    $element = $this->byId("user_password_first");
    $element->click();
    $element->clear();
    $element->value("test1234");
    // setElementText
    $element = $this->byId("user_password_second");
    $element->click();
    $element->clear();
    $element->value("test1234");
    // clickElement
    $this->byCssSelector("input.button")->click();
    // waitForTextPresent
    $this->waitUntil(function() use ($test) {
      try {
        $boolean = (strpos($test->byTag('html')->text(), "testing@example.com") !== false);
      } catch (\Exception $e) {
        $boolean = false;
      }
      return $boolean === true ?: null;
    });
    // clickElement
    $this->byCssSelector("span.iconRemove.iconSmall")->click();
    // waitForElementPresent
    $this->waitUntil(function() use ($test) {
      try {
        $boolean = ($test->byLinkText("Delete") instanceof \PHPUnit_Extensions_Selenium2TestCase_Element);
      } catch (\Exception $e) {
        $boolean = false;
      }
      return $boolean === true ?: null;
    });
    // clickElement
    $this->byLinkText("Delete")->click();
    // waitForTextPresent
    $this->waitUntil(function() use ($test) {
      try {
        $boolean = (strpos($test->byTag('html')->text(), "The user has been deleted") !== false);
      } catch (\Exception $e) {
        $boolean = false;
      }
      return $boolean === true ?: null;
    });
  }
}
