<?php

namespace Mapbender\CoreBundle\Tests;

use Symfony\Component\Process\Process;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SeleniumPythonTest extends WebTestCase
{
    public function setUp()
    {
        if (PHP_MINOR_VERSION == 3) {
            $this->markTestIncomplete('This test does not run on PHP 5.3.');
            return;
        }
    }

    public function moduleProvider()
    {
        $data = array();
        $glob = glob(dirname(__FILE__) . '/SeleniumIdeTests/*.py');
        foreach ($glob as $file) {
            $data[] = array($file);
        }
        return $data;
    }

    /**
     * @dataProvider moduleProvider
     */
    public function testSelenium($module)
    {
        putenv('TEST_WEB_SERVER_HOST=' . TEST_WEB_SERVER_HOST);
        putenv('TEST_WEB_SERVER_PORT=' . TEST_WEB_SERVER_PORT);
        putenv('TEST_SCREENSHOT_PATH=' . TEST_SCREENSHOT_PATH);

        $process = new Process('python ' . $module);
        $process->setTimeout(1000);
        $process->run();

        $this->assertEquals(true, $process->isSuccessful(), $process->getErrorOutput());
    }
}
