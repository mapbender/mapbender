<?php
namespace Mapbender\PrintBundle\Tests\Component;

//require_once realpath(dirname(__DIR__) . '/../../../../app/AppKernel.php');
use Symfony\Component\BrowserKit\Cookie;
use Doctrine\DBAL\Portability\Connection;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Base
 *
 * @package   Mapbender\PrintBundle\Tests\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class Base extends WebTestCase
{

    protected $client;
    protected $connection;
    private   $testString;

    public function testDbConnection()
    {
        $this->assertEquals(2, $this->db()->fetchColumn('SELECT 1+1'));
    }

    public function testHasContainer()
    {
        $this->assertNotEquals(null, $this->getClient()->getContainer());
    }

    /**
     * Get web-client
     *
     * @return Client
     */
    protected function getClient()
    {
        if (!$this->client) {
            $this->client = static::createClient();
            $container    = $this->client->getContainer();
            $session      = $container->get('session');
//            $firewall     = 'secured_area';
//            $token        = new UsernamePasswordToken('andriy', 'test', $firewall, array('ROLE_ADMIN'));
//            $session->set('_security_' . $firewall, serialize($token));
//            $session->save();
            $cookie = new Cookie($session->getName(), $session->getId());
            $this->client->getCookieJar()->set($cookie);
        }

        return $this->client;
    }

    /**
     * @return Connection
     */
    protected function db()
    {
        return $this->connection ? $this->connection : $this->connection = $this->getContainer()->get('doctrine')->getConnection();
    }

    /**
     * @return array
     */
    protected function fetchArray($sql, $params = array())
    {
        return array_values($this->db()->fetchArray($sql, $params));
    }


    /**
     * Generate and save test string
     *
     * @return string
     */
    public function getTestString()
    {
        if (!$this->testString) {
            $this->testString = 'test-' . rand(100, 1000000);
        }
        return $this->testString;
    }

    /**
     * @param Application $application
     * @param string      $command
     * @param array       $options
     * @return mixed
     */
    protected function runConsole($application, $command, array $options = array())
    {
        return $application->run(new ArrayInput(array_merge($options,
            array('command' => $command,
                  '-e'      => "test",
                  '-q'      => null,))));
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->getClient()->getContainer();
    }
}