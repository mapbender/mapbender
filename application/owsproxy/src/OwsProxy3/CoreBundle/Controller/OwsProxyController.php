<?php

namespace OwsProxy3\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use OwsProxy3\CoreBundle\Component\WmsProxy;
use OwsProxy3\CoreBundle\Component\Url;

/**
 * Description of OwsProxyController
 *
 * @author A.R.Pour
 */
class OwsProxyController extends Controller {
    
    /**
     * @Route("/")
     */
    public function entryPointAction() {
        if(!isset($_GET["url"])) {
            throw new \RuntimeException('url parameter not found!');
        }
        
        // Get the URL
        $url = new Url($_GET["url"]);

        // Add get params to URL
        foreach($_GET as $key => $val) {
            if($key === "url") continue;
            
            $url->addParam($key, $val);
        }

        // Switch proxy
        switch(strtolower($url->getParam("service",true))) {
            case 'wms':
                try {
                    $proxy = new WmsProxy($this->container);
                    return $proxy->handle($url);
                } catch(\Exception $e) {
                    return $this->badGateway();
                }
            default:
                throw new \RuntimeException('Unknown Service Type');
        }
    }
    
    private function badGateway() {
        $response = new Response();
        $html = $this->render("OwsProxy3CoreBundle::badGateway.html.twig",array());
        $response->headers->set('Content-Type', 'text/html');
        $response->setContent($html->getContent());
        return $response;
    }
}
