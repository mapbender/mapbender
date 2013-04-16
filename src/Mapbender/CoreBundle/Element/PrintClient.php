<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\PrintBundle\Component\OdgParser;

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
        return "Please give me a description";
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
            'css' => array(
                'mapbender.element.printClient.css'
            ));
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
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
        $forms = array();
        if (null !== $configuration['optional_fields']) 
        {
            $form_builder = $this->container->get('form.factory')->createNamedBuilder('extra', 'form', null, array(
                'csrf_protection' => false
            ));
            foreach($configuration['optional_fields'] as $k => $c) {
                $options = array_key_exists('options', $c) ? $c['options'] : array();
                $form_builder->add($k, $c['type'], $options);
            }
            $forms['extra'] = $form_builder->getForm()->createView();
        }
        
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:printclient.html.twig',
                array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration(),
                    'forms' => $forms));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        switch($action)
        {
            case 'direct':
                
                $request = $this->container->get('request');
                $data = $request->request->all();
                
                foreach($request->request->keys() as $key) {
                    $request->request->remove($key);
                }
                // keys, remove
                foreach($data['layers'] as $idx => $layer) {
                    $data['layers'][$idx ]= json_decode($layer, true);
                }
                $content = json_encode($data);
                
                // Forward to Printer Service URL using OWSProxy
                $configuration = $this->getConfiguration();
                $url = (null !== $configuration['printer']['service'] ?
                        $configuration['printer']['service'] :
                        $this->container->get('router')->generate('mapbender_print_print_service', array(), true));

                return $this->container->get('http_kernel')->forward(
                    'OwsProxy3CoreBundle:OwsProxy:genericProxy',
                    array(
                        'url' => $url,
                        'content' => $content
                    )
                );
                
            case 'queued':
                $content = $this->container->get('request')->getContent();
                if(empty($content))
                {
                    throw new \RuntimeException('No Request Data received');
                }

                // Forward to Printer Service URL using OWSProxy
                $configuration = $this->getConfiguration();
                $url = (null !== $configuration['printer']['service'] ?
                        $configuration['printer']['service'] :
                        $this->container->get('router')->generate('mapbender_print_print_service', array(), true));
                return $this->container->get('http_kernel')->forward(
                    'OwsProxy3CoreBundle:OwsProxy:genericProxy',
                    array(
                        'url' => $url,
                        'content' => $content
                    )
                );
            case 'template':
                $response = new Response();
                $response->headers->set('Content-Type', 'application/json');        
                $request = $this->container->get('request');
                $data = json_decode($request->getContent(),true);          
                $odgParser = new OdgParser();
                $size = $odgParser->getMapSize($data['template']);
                $response->setContent($size->getContent());
                return $response;
        }
    }

}
