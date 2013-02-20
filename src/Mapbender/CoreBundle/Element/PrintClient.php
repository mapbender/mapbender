<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

class PrintClient extends Element 
{
    static public function getClassTitle()
    {
        return "Print Client";
    }

    static public function getClassDescription() 
    {
        return "";
    }

    static public function getClassTags() 
    {
        return array('Print');
    }

    public function getAssets() 
    {
        return array(
            'js' => array(
                'mapbender.element.printClient.js'),
            'css' => array(
                'mapbender.element.printClient.css'
            ));
    }
    
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

    public function getWidgetName()
    {
        return 'mapbender.mbPrintClient';
    }
    
    public function render() 
    {
        $configuration = $this->getConfiguration();
        $forms = array();
        foreach($configuration['formats'] as $key => $config) {
            if(!array_key_exists('optional_fields', $config) or !is_array($config['optional_fields'])) {
                continue;
            }

            $form_builder = $this->container->get('form.factory')->createNamedBuilder('extra', 'form', null, array(
                'csrf_protection' => false
            ));
            foreach($config['optional_fields'] as $k => $c) {
                $options = array_key_exists('options', $c) ? $c['options'] : array();
                $form_builder->add($k, $c['type'], $options);
            }
            $forms[$key] = $form_builder->getForm()->createView();
        }
        
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:printclient.html.twig',
                array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration(),
                    'forms' => $forms));
    }
    
    public function httpAction($action)
    {
        switch($action) {
            case 'direct':
                $content = $this->container->get('request')->getContent();
                if (empty($content)){
                    throw new \RuntimeException('No Request Data received');
                }
                // Forward to Printer Service URL using OWSProxy
                $configuration = $this->getConfiguration();
                return  $this->container->get('http_kernel')->forward(
                    'OwsProxy3CoreBundle:OwsProxy:genericProxy',
                    array(
                        'url' => $configuration['printer']['service'],
                        'content' => $content
                    )
                );
        }
    }
}
