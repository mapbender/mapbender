<?php

namespace Mapbender\PrintBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\PrintBundle\Component\PrintService;


class PrintController extends Controller
{
    /**
     *
     * @Route("/")
     * 
     */
    public function serviceAction() 
    {      
        $content = $this->get('request')->getContent();       
        $container = $this->container;   
        $printservice = new PrintService($container);
        $printservice->doPrint($content);
        return new Response(''); 
    }
    
}
