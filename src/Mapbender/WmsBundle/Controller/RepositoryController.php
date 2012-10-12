<?php

/**
 * @author Christian Wygoda
 */

namespace Mapbender\WmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WmsBundle\Form\Type\WmsSourceSimpleType;
use Mapbender\WmsBundle\Form\Type\WmsSourceType;
use Mapbender\Component\HTTP\HTTPClient;

/**
 * @ManagerRoute("/repository/wms")
 */
class RepositoryController extends Controller {
    /*
     * Make sure the url is correct,. add missing parameters and filter sessionids
     */

    protected function capabilitiesURLFixup($url, $user = null, $password = null) {
        $sessionids = array(
            "PHPSESSID",
            "jsessionid"
        );
        $parsedUrl = HTTPClient::parseUrl($url);
        if($user !== null && $user != ""){
            $parsedUrl["user"] = $user;
            if($password !== null && $password != ""){
                $parsedUrl["pass"] = $password;
            }
        }
        $parsedQuery = HTTPClient::parseQueryString($parsedUrl['query']);

        $resultQuery = array();
        foreach ($parsedQuery as $key => $value) {
            if (!in_array($key, $sessionids)) {
                $resultQuery[$key] = $value;
            }
        }

        $parsedUrl['query'] = HTTPClient::buildQueryString($resultQuery);
        return HTTPClient::buildUrl($parsedUrl);
    }

    /**
     * @ManagerRoute("/new")
     * @Method({ "GET" })
     * @Template
     */
    public function newAction() {
        $form = $this->get("form.factory")->create(new WmsSourceSimpleType(), new WmsSource());
        return array(
            "form" => $form->createView()
        );
    }

    /**
     * @ManagerRoute("/new")
     * @Method({ "POST" })
     * @Template("MapbenderWmsBundle:Repository:new.html.twig")
     */
    public function createAction() {
        $wmssource_req = new WmsSource();
        $form = $this->get("form.factory")->create(new WmsSourceSimpleType(), $wmssource_req);
        $form->bindRequest($this->get('request'));
        $getcapa_url = trim($this->capabilitiesURLFixup($wmssource_req->getOriginUrl()));
        if ($form->isValid() && $getcapa_url) {
            $wmssource_req->setOriginUrl($getcapa_url);
            $client = new HTTPClient($this->container);
            if ($wmssource_req->getUsername()) {
                $client->setUsername($wmssource_req->getUsername());
                $client->setPassword($wmssource_req->getPassword());
            }
            $getcapa_url_usrPwd = $this->capabilitiesURLFixup(
                    $wmssource_req->getOriginUrl(),
                    $wmssource_req->getUsername(),
                    $wmssource_req->getPassword());
            $result = $client->open($getcapa_url_usrPwd);
            if($result->getStatusCode() == 200){
                if(!$result->getData()){
                    $this->get("logger")->debug("$getcapa_url_usrPwd returned no data");
                    throw new \Exception("Preview: Service '$getcapa_url_usrPwd' returned no Data");
                }
                $wmsParser = WmsCapabilitiesParser::create($result->getData());
                $wmssource = $wmsParser->parse();
                if(!$wmssource){
                    $this->get("logger")->debug("Could not parse data for url '$getcapa_url_usrPwd'");
                    throw new \Exception("Preview: Could not parse data for url '$getcapa_url_usrPwd'");
                }
            }else{
                throw new \Exception("Preview: Server said '".$result->getStatusCode() . " ". $result->getStatusMessage(). "'");
            }
        }
        if (!$getcapa_url) {
            $this->get('session')->setFlash('error', "url not set");
        }
        return $this->render(
                        "MapbenderWmsBundle:Repository:new.html.twig", array("form" => $form->createView()));
    }

}

