<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

/**
 * 
 */
class PrintClient extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Print Client";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('Print');
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.printClient.js'),
            'css' => array());
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "autoOpen" => false,
            "print_directly" => true,
            "printer" => Array(
                "service" => null,
                "metadata" => null,
            ),
            "formats" => Array(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbPrintClient';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $configuration = $this->getConfiguration();
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:printclient.html.twig',
                                 array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        switch($action)
        {
            case 'direct':
                $content = $this->container->get('request')->getContent();
                if(empty($content))
                {
                    throw new \RuntimeException('No Request Data received');
                }

                // Forward to Printer Service URL using OWSProxy
                $configuration = $this->getConfiguration();
                $this->container->get('http_kernel')->forward(
                        'OwsProxy3CoreBundle:OwsProxy:genericProxy',
                        array(
                    'url' => $configuration['printer']['service']
                        )
                );
        }
    }

}
