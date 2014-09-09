<?php

namespace Mapbender\PrintBundle\Tests\Component;

use Mapbender\CoreBundle\Component\Event\BaseEvent;
use Mapbender\PrintBundle\Component\IdentityPriorityVoter;
use Mapbender\PrintBundle\Component\PrintQueueManager;
use Satooshi\Bundle\CoverallsBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\app\AppKernel;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class PrintQueueManagerTest
 *
 * @package   Mapbender\PrintBundle\Tests\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class PrintQueueManagerTest extends Base
{
    /** @var PrintQueueManager */
    protected $manager;

    public function  testHeavyLoad()
    {
        for($i = 0; $i < 1000; $i++ ){
            $this->manager()->add($this->getPayload());
        }
    }

    public function testGetQueues(){
        $queues = $this->manager()->getUserQueueInfos(1);
    }

    public  function testAddRemove()
    {
        $manager = $this->manager();

        // check add
        $queue = $manager->add($this->getPayload());
        $id = $queue->getId();
        $this->assertGreaterThan(0, $id);

        // check event listening
        $manager->on(PrintQueueManager::STATUS_RENDERING_STARTED, function (BaseEvent $event){
            echo 'rendering started!';
        });

        $manager->on(PrintQueueManager::STATUS_RENDERING_COMPLETED, function (BaseEvent $event){
            echo 'rendering completed!';
        });
        $manager->on(PrintQueueManager::STATUS_RENDERING_SAVED, function (BaseEvent $event){
            echo 'rendering saved!';
        });

        $manager->on(PrintQueueManager::STATUS_RENDERING_SAVE_ERROR, function (BaseEvent $event){
            echo 'pdf can\'t be saved!';
        });

        // check rendering
        $manager->render($queue);
        $this->assertEquals(is_file($manager->getPdfPath($queue)),true);

        // check find by id
        $this->assertEquals($id, $this->manager()->find($id)->getId());

        // check remove
        $this->manager()->remove($queue);
        $this->assertEquals(!is_file($manager->getPdfPath($queue)),true);
    }

    public function testRendering(){
        $manager = $this->manager();
        $manager->renderNext();
    }

    public function testCleaining(){
        $this->manager()->clean();
    }

    public  function testRemoveById(){
        $queue = $this->manager()->add($this->getPayload());
        $this->assertEquals(1,$this->manager()->removeById($queue->getId()));
    }

    /**
     * @return PrintQueueManager
     */
    private function manager()
    {
        if (!$this->manager) {
            $client        = $this->getClient();
            $payload       = array();
            $this->manager = $client->getContainer()->get('mapbender.print.queue_manager');
//            $this->manager = new PrintQueueManager($client->getContainer(), new IdentityPriorityVoter($payload));
        }
        return $this->manager;
    }

    public function getPayload()
    {
        return array('template'       => 'a4portrait',
                     'quality'        => '72',
                     'scale_select'   => '500',
                     'scale_text'     => '',
                     'rotation'       => '10',
                     'extra'          => array('title' => $this->getTestString()),
                     'format'         => 'a4',
                     'extent'         => array('width' => '95', 'height' => '118.5'),
                     'center'         => array('x' => '366076.05833545', 'y' => '5621338.5403438'),
                     'file_prefix'    => 'mapbender3',
                     'extent_feature' => array(0 => array('x' => 366018.99131265999, 'y' => 5621288.4387729),
                                               1 => array('x' => 366112.54804919002,
                                                          'y' => 5621271.9421960004),
                                               2 => array('x' => 366133.12535823998,
                                                          'y' => 5621388.6419147002),
                                               3 => array('x' => 366039.56862171,
                                                          'y' => 5621405.1384915998)),
                     
                     'layers'         => array(0 => array('type'    => 'wms',
                                                          'url'     => 'http://osm-demo.wheregroup.com/service?_signature=0%3AhDYlpow95Vwcp9kOyf1uCZTEY5E&TRANSPARENT=TRUE&FORMAT=image%2Fpng&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&LAYERS=osm&SRS=EPSG%3A25832&BBOX=360277.26201564,5616576.0308188,371874.85465526,5626101.0498688&WIDTH=1972&HEIGHT=1620',
                                                          'opacity' => 1),
                                               1 => array('type'    => 'wms',
                                                          'url'     => 'http://wms.wheregroup.com/cgi-bin/mapbender_user.xml?_signature=0%3AhDYlpow95Vwcp9kOyf1uCZTEY5E&TRANSPARENT=TRUE&FORMAT=image%2Fpng&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&LAYERS=Mapbender_User,Mapbender_Names&SRS=EPSG%3A25832&BBOX=360277.26201564,5616576.0308188,371874.85465526,5626101.0498688&WIDTH=1972&HEIGHT=1620',
                                                          'opacity' => 0.84999999999999998)),
                     'overview'       => array(0 => array('url'   => 'http://osm-demo.wheregroup.com/service?_signature=0%3AhDYlpow95Vwcp9kOyf1uCZTEY5E&LAYERS=osm&FORMAT=image%2Fpng&TRANSPARENT=TRUE&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&SRS=EPSG%3A25832&BBOX=360277.26201564,5616576.0308188,371874.85465526,5626101.0498688&WIDTH=300&HEIGHT=150',
                                                          'scale' => 164375)));
    }
}