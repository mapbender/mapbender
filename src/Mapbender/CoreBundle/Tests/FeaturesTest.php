<?php
/**
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 18.03.2015 by WhereGroup GmbH & Co. KG
 */

namespace Mapbender\CoreBundle\Tests;

use Mapbender\CoreBundle\Component\Features;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class FeaturesTest
 *
 * @package   Mapbender\CoreBundle\Tests
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2015 by WhereGroup GmbH & Co. KG
 */
class FeaturesTest extends WebTestCase
{
    public function testGet()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        /** @var Features $features */
        $featureType  = $container->get('features')->get('addresses');
        $platformName = $featureType->isPostgres();
        var_dump($platformName);

//        var_dump($container->getParameter('featureTypes')['addresses']);
        die();
        $features->get('addresses');
//        $feature   = $features->save(array());
    }
}