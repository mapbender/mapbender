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
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WmsBundle\Form\Type\WmsInstanceInstanceLayersType;
use Mapbender\WmsBundle\Form\Type\WmsSourceSimpleType;
use Mapbender\WmsBundle\Form\Type\WmsSourceType;
use Mapbender\WmsBundle\Form\Type\WmsInstanceType;
use Mapbender\Component\HTTP\HTTPClient;
use Mapbender\CoreBundle\Component\Utils;

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

        $resultQuery    = array();
        $found_version  = false;
        $found_request  = false;
        $found_service  = false;
        
        $default_version = "1.3.0";
        foreach ($parsedQuery as $key => $value) {
            if (!in_array($key, $sessionids)) {
                $resultQuery[$key] = $value;
            }
            if(strtolower($key)  == "version"){ $found_version = true; }
            if(strtolower($key)  == "request"){ $found_request = true; }
            if(strtolower($key)  == "service"){ $found_service = true; }
        }
        if(!$found_version) { $resultQuery["VERSION"] = $default_version; } 
        if(!$found_request) { $resultQuery["REQUEST"] = "GetCapabilities"; } 
        if(!$found_service) { $resultQuery["service"] = "WMS"; } 
        

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
     * @ManagerRoute("/start")
     * @Method({ "GET" })
     * @Template
     */
    public function startAction() {
        $form = $this->get("form.factory")->create(new WmsSourceSimpleType(), new WmsSource());
        return array(
            "form" => $form->createView()
        );
    }

    /**
    * @ManagerRoute("{wms}")
    * @Method({ "GET"})
    * @Template
    */
    public function viewAction(WmsSource $wms){
        return array("wms" => $wms);
    
/*
        return $this
            ->get("templating")
            ->render("MapbenderWmsBundle:Repository:view.html.twig",array("wms" => $wms));
*/
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
                $doc = WmsCapabilitiesParser::createDocument($result->getData());
                $version = WmsCapabilitiesParser::getVersion($doc);
                if(!WmsCapabilitiesParser::isVersionSupported($version)){
                    $this->get('session')->setFlash('error', 'The WMS version "'
                            . $version . '" is not supported.');
                    return $this->redirect($this->generateUrl(
                            "mapbender_manager_repository_new",array(), true));
                }
                $wmsParser = WmsCapabilitiesParser::getParser($doc);
                $wmssource = $wmsParser->parse();
                if(!$wmssource){
                    $this->get("logger")->debug("Could not parse data for url '$getcapa_url_usrPwd'");
                    throw new \Exception("Preview: Could not parse data for url '$getcapa_url_usrPwd'");
                }
                $wmsWithSameTitle  =$this->getDoctrine()
                    ->getEntityManager()
                    ->getRepository("MapbenderWmsBundle:WmsSource")
                    ->findByTitle($wmssource->getTitle());

                if(count($wmsWithSameTitle) > 0) {
                    $wmssource->setAlias(count($wmsWithSameTitle));
                }

                $wmssource->setOriginUrl($getcapa_url_usrPwd);

                $this->getDoctrine()->getEntityManager()->persist($wmssource);
                $this->getDoctrine()->getEntityManager()->flush();
            }else{
                throw new \Exception("Preview: Server said '".$result->getStatusCode() . " ". $result->getStatusMessage(). "'");
            }
        }
        if (!$getcapa_url) {
            $this->get('session')->setFlash('error', "url not set");
        }
        return $this->redirect($this->generateUrl("mapbender_manager_repository_view",array("sourceId"=>$wmssource->getId()), true));
    }
    
    /**
     * deletes a WmsSource
     * @ManagerRoute("/{sourceId}/delete")
     * @Method({"GET"})
    */
    public function deleteAction($sourceId){
        $wmssource = $this->getDoctrine()
                    ->getRepository("MapbenderWmsBundle:WmsSource")
                    ->find($sourceId);
        $wmsinstances = $this->getDoctrine()
                    ->getRepository("MapbenderWmsBundle:WmsInstance")
                    ->findBySource($sourceId);
        $em = $this->getDoctrine()->getEntityManager();
        foreach ($wmsinstances as $wmsinstance) {
            $em->remove($wmsinstance);
            $em->flush();
        }
        $this->removeRecursive($wmssource->getRootlayer(), $em);
        $em->remove($wmssource);
        $em->flush();
        $this->get('session')->setFlash('info',"Service deleted");
        return $this->redirect($this->generateUrl("mapbender_manager_repository_index"));
    }

    /**
     * Recursively remove a nested Layerstructure
     * @param GroupLayer
     * @param EntityManager
     */
    public function removeRecursive(WmsLayerSource $wmslayer, $em){
//        $this->removeRecursive($wmssource->getRootlayer(),
//                        $this->getDoctrine()->getEntityManager());
        foreach($wmslayer->getSublayer() as $sublayer){
            $this->removeRecursive($sublayer, $em);
        }
        $em->remove($wmslayer);
        $em->flush();
    }
    
    /**
     * Edits, saves the WmsInstance
     * 
     * @ManagerRoute("/instance/{slug}/{instanceId}")
     * @Template("MapbenderWmsBundle:Repository:instance.html.twig")
     */ 
    public function instanceAction($slug, $instanceId){
        if ($this->getRequest()->getMethod() == 'POST'){ //save
            $wmsinstance = $this->getDoctrine()
                    ->getRepository("MapbenderWmsBundle:WmsInstance")
                    ->find($instanceId);
            $wmsinstance_req = new WmsInstance();
            $wmsinstance_req->setSource($wmsinstance->getSource());
            $form_req = $this->createForm(
                    new WmsInstanceInstanceLayersType(), $wmsinstance_req);
            $form_req->bindRequest($this->get('request'));
            $form = $this->createForm(
                    new WmsInstanceInstanceLayersType(), $wmsinstance);
            $form->bindRequest($this->get('request'));
            $wmsinstance->setTransparency(
                    Utils::getBool($wmsinstance_req->getTransparency()));
            $wmsinstance->setVisible(
                    Utils::getBool($wmsinstance_req->getVisible()));
            $wmsinstance->setOpacity(
                     Utils::getBool($wmsinstance_req->getOpacity()));
            $wmsinstance->setProxy(
                     Utils::getBool($wmsinstance_req->getProxy()));
            $wmsinstance->setTiled(
                     Utils::getBool($wmsinstance_req->getTiled()));
            foreach ($wmsinstance->getLayers() as $layer) {
                foreach ($wmsinstance_req->getLayers() as $layer_tmp) {
                    if($layer_tmp->getId() === $layer->getId()){
                        $layer->setActive(Utils::getBool(
                                $layer_tmp->getActive()));
                        $layer->setSelected(Utils::getBool(
                                $layer_tmp->getSelected()));
                        $layer->setSelectedDefault(Utils::getBool(
                                $layer_tmp->getSelectedDefault()));
                        $layer->setInfo(Utils::getBool(
                                $layer_tmp->getInfo(),true));
                        $layer->setAllowinfo(Utils::getBool(
                                $layer_tmp->getAllowinfo(),true));
                        break;
                    }
                }
            }
            if($form->isValid()) { //save
                $this->getDoctrine()->getEntityManager()->persist($wmsinstance);
//                $configuration = $wmsinstance->getConfiguration();
//                foreach ($wmsinstance->getMblayer() as $mblayer) {
//                    $mblayer->setConfiguration($configuration);
//                    $this->getDoctrine()->getEntityManager()->persist($mblayer);
//                }
                $this->getDoctrine()->getEntityManager()->flush();
                
                $this->get('session')->setFlash(
                        'notice', 'Your Wms Instance has been changed.');
                return $this->redirect($this->generateUrl(
                        'mapbender_manager_application_edit',
                        array("slug" => $slug)) . '#layersets');
            } else { // edit
                return array(
                    "form" => $form->createView(),
                    "slug" => $slug,
                    "instance" => $wmsinstance);
            }
        } else { // edit
            $wmsinstance = $this->getDoctrine()
                    ->getRepository("MapbenderWmsBundle:WmsInstance")
                    ->find($instanceId);
            $form = $this->createForm(
                    new WmsInstanceInstanceLayersType(), $wmsinstance);
            $fv = $form->createView();
            return array(
                "form" => $form->createView(),
                "slug" => $slug,
                "instance" => $wmsinstance);
        }
    }
    
    /**
     * Changes the priority of WmsInstanceLayers
     * 
     * @ManagerRoute("/{slug}/instance/{layersetId}/weight/{instanceId}")
     */ 
    public function instanceWeightAction($slug, $layersetId, $instanceId){
        $number = $this->get("request")->get("number");
//        $number = intval($number) - 1;
        $instance = $this->getDoctrine()
            ->getRepository('MapbenderWmsBundle:WmsInstance')
            ->findOneById($instanceId);

        if(!$instance) {
            throw $this->createNotFoundException('The wms instance with"
                ." the id "'. $instanceId .'" does not exist.');
        }
        if(intval($number) === $instance->getWeight()){
            return new Response(json_encode(array(
                'error' => '',
                'result' => 'ok')), 200,
                    array('Content-Type' => 'application/json'));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $instance->setWeight($number);
        $em->persist($instance);
        $em->flush();
        $query = $em->createQuery(
                "SELECT i FROM MapbenderWmsBundle:WmsInstance i"
                ." WHERE i.layerset=:lsid ORDER BY i.weight ASC");
        $query->setParameters(array("lsid" => $layersetId));
        $instList = $query->getResult();
        
        $num = 0;
        foreach ($instList as $inst) {
            if($num === intval($instance->getWeight())){
                if($instance->getId() === $inst->getId()){
                    $num++;
                } else {
                    $num++;
                    $inst->setWeight($num);
                    $num++;
                }
            } else {
                if($instance->getId() !== $inst->getId()){
                    $inst->setWeight($num);
                    $num++;
                }
            }
        }
        foreach ($instList as $inst) {
            $em->persist($inst);
        }
        $em->flush();
        return new Response(json_encode(array(
            'error' => '',
            'result' => 'ok')), 200, array(
                'Content-Type' => 'application/json'));
    }
    
    /**
     * Changes the priority of WmsInstanceLayers
     * 
     * @ManagerRoute("/{slug}/instance/{instanceId}/priority/{instLayerId}")
     */ 
    public function instanceLayerPriorityAction($slug, $instanceId, $instLayerId){
        $number = $this->get("request")->get("number");
//        $number = intval($number) - 1;
        $instLay = $this->getDoctrine()
            ->getRepository('MapbenderWmsBundle:WmsInstanceLayer')
            ->findOneById($instLayerId);

        if(!$instLay) {
//            throw $this->createNotFoundException('The wms instance layer with"
//                ." the id "'. $instanceId .'" does not exist.');
            return new Response(json_encode(array(
                'error' => 'The wms instance layer with'
                    .' the id "'. $instanceId .'" does not exist.',
                'result' => '')), 200,
                    array('Content-Type' => 'application/json'));
        }
        if(intval($number) === $instLay->getPriority()){
            return new Response(json_encode(array(
                'error' => '',
                'result' => 'ok')), 200,
                    array('Content-Type' => 'application/json'));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $instLay->setPriority($number);
        $em->persist($instLay);
        $em->flush();
        $query = $em->createQuery(
                "SELECT il FROM MapbenderWmsBundle:WmsInstanceLayer il"
                ." WHERE il.wmsinstance=:wmsi ORDER BY il.priority ASC");
        $query->setParameters(array("wmsi" => $instanceId));
        $instList = $query->getResult();
        
        $num = 0;
        foreach ($instList as $inst) {
            if($num === intval($instLay->getPriority())){
                if($instLay->getId() === $inst->getId()){
                    $num++;
                } else {
                    $num++;
                    $inst->setPriority($num);
                    $num++;
                }
            } else {
                if($instLay->getId() !== $inst->getId()){
                    $inst->setPriority($num);
                    $num++;
                }
            }
        }
        foreach ($instList as $inst) {
            $em->persist($inst);
        }
        $em->flush();
        return new Response(json_encode(array(
            'error' => '',
            'result' => 'ok')), 200, array(
                'Content-Type' => 'application/json'));
    }
}
