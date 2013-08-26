<?php

namespace Mapbender\WmcBundle\Component;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\StateHandler;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\CoreBundle\Form\Type\StateType;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmcBundle\Component\WmcParser;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcLoadType;
use Mapbender\WmcBundle\Form\Type\WmcType;
use Symfony\Component\HttpFoundation\Response;

class WmcHandler
{

    protected $element;
    protected $container;
    protected $application;

    /**
     * Creates a wmc handler
     * 
     * @param Element $element
     */
    public function __construct(Element $element, $application, $container)
    {
	$this->element = $element;
	$this->application = $application;
	$this->container = $container;
    }
    

    /**
     * Returns a state from a state id
     * 
     * @return Mapbender\CoreBundle\Entity\State or null.
     */
    public function findState($stateid)
    {
	$state = null;
	if($stateid)
	{
	    $state = $this->container->get('doctrine')
		->getRepository('Mapbender\CoreBundle\Entity\State')
		->find($stateid);
	}
	return $state;
    }
    
    /**
     * Saves and returns a saved state
     * 
     * @param array $jsonState a mapbender state
     * @return \Mapbender\CoreBundle\Entity\State or null
     */
    public function saveState($jsonState)
    {
	$state = null;
	if($jsonState !== null)
	{
	    $state = new State();
	    $state->setServerurl($this->getBaseUrl());
	    $state->setSlug($this->application->getSlug());
	    $state->setTitle("SuggestMap");
	    $state->setJson($jsonState);
	    $em = $this->container->get('doctrine')->getEntityManager();
	    $em->persist($state);
	    $em->flush();
	}
	return $state;
    }
    
    

//
//    /**
//     * @inheritdoc
//     */
//    static public function getClassTitle()
//    {
//        return "WmcHandler";
//    }
//
//    /**
//     * @inheritdoc
//     */
//    static public function getClassDescription()
//    {
//        return "";
//    }
//
//    /**
//     * @inheritdoc
//     */
//    static public function getClassTags()
//    {
//        return array("wmc", "handler");
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public static function getDefaultConfiguration()
//    {
//        return array(
//            "tooltip"               => null,
//            "target"                => null,
//            "keepBaseSources"       => false,
//            "useSuggestMap"         => false,
//            'receiver'              => array("email"),
//            "useEditor"             => false,
//            "accessEditorAnonymous" => false,
//            "accessGroupsEditor"    => array(),
//            "useLoader"             => false,
//            "accessLoaderAnonymous" => false,
//            "accessGroupsLoader"    => array(),
//        );
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public static function getType()
//    {
//        return 'Mapbender\WmcBundle\Element\Type\WmcHandlerAdminType';
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public static function getFormTemplate()
//    {
//        return 'MapbenderWmcBundle:ElementAdmin:wmchandler.html.twig';
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function getWidgetName()
//    {
//        return 'mapbender.mbWmcHandler';
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function getAssets()
//    {
//        $configuration = $this->getConfiguration();
//        $js            = array('jquery.form.js', 'mapbender.element.wmchandler.js');
////        if($configuration["useSuggestMap"]) {
////            $js[] = 'mapbender.element.wmchandler_suggestmap.js';
////        }
////        if($configuration["useEditor"]) {
////            $js[] = 'mapbender.element.wmchandler_editor.js';
////        }
////        if($configuration["useLoader"]) {
////            $js[] = 'mapbender.element.wmchandler_loader.js';
////        }
//        return array(
//            'js'  => $js,
//            'css' => array()
//        );
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function getConfiguration()
//    {
//        $configuration         = parent::getConfiguration();
//        $toload                = array();
//        $wmcid                 = $this->container->get('request')->get('wmc');
//        if($wmcid) $toload["wmc"]         = $wmcid;
//        $stateid               = $this->container->get('request')->get('state');
//        if($stateid) $toload["state"]       = $stateid;
//        if(count($toload) > 0) $configuration["load"] = $toload;
//        return $configuration;
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function render()
//    {
//        $wmc          = new Wmc();
//        $config       = $this->getConfiguration();
//        $accessEditor = $this->accessAnonymous("accessEditorAnonymous") || $this->accessGroups("accessGroupsEditor");
//
//        $form = $this->container->get("form.factory")->create(new WmcLoadType(),
//                                                              $wmc);
//        $html = $this->container->get('templating')
//            ->render('MapbenderWmcBundle:Element:wmchandler.html.twig',
//                     array(
//            'id'            => $this->getId(),
//            'configuration' => $config,
//            'title'         => $this->getTitle(),
//            'form'          => $form->createView(),
//            'accessEditor'  => $accessEditor));
//        return $html;
//    }
//
//    public function httpAction($action)
//    {
//        $session = $this->container->get("session");
//
//        if($session->get("proxyAllowed", false) !== true)
//        {
//            throw new AccessDeniedHttpException('You are not allowed to use this proxy without a session.');
//        }
//        switch($action)
//        {
//            case 'get':
//                return $this->getWmc();
//                break;
//            case 'list':
//                return $this->getWmcList();
//                break;
//            case 'remove':
//                return $this->removeWmc();
//                break;
//            case 'save':
//                return $this->saveWmc();
//                break;
//            case 'load':
//                $type = $this->container->get('request')->get("type", null);
//                $id   = $this->container->get('request')->get("_id", null);
//                if($type === "wmc") return $this->loadWmc($id);
//                else if($type === "state") return $this->loadState($id);
//                break;
//            case 'state':
//                return $this->saveState();
//                break;
//            case 'loadxml':
//                return $this->loadXml();
//                break;
//            case 'wmcasxml':
//                return $this->getWmcAsXml();
//                break;
////            case 'wmcasjson':
////                return $this->getWmcAsJson();
////                break;
//            default:
//                throw new NotFoundHttpException('No such action');
//        }
//    }

    private function getWmcAsXml()
    {
	$request = $this->container->get('request');
	$wmc = Wmc::create();
	$form = $this->container->get("form.factory")->create(new WmcLoadType(),
	    $wmc);
	$form->bindRequest($request);
	if($form->isValid())
	{ //TODO: Is file an image (jpg/png/gif?)
	    $state = $wmc->getState();
	    if($state !== null && $state->getJson() !== null)
	    {
		$state->setServerurl($this->element->getBaseUrl());
		$state->setSlug($this->application->getSlug());
		$state->setTitle("Mapbender State");
		$wmc->setWmcid(round((microtime(true) * 1000)));
		$xml = $this->container->get('templating')
		    ->render('MapbenderWmcBundle:Wmc:wmc110_simple.xml.twig',
		    array(
		    'wmc' => $wmc));
		$response = new Response();
		$response->setContent($xml);
		$response->headers->set('Content-Type', 'application/xml');
		$response->headers->set('Content-Disposition', 'attachment; filename=wmc.xml');
		return $response;
	    }
	}
	return new Response(json_encode(array(
		"error" => 'WMC:  can not be loaded.')), 200,
	    array('Content-Type' => 'application/json'));
    }

    public function loadXml()
    {
	$request = $this->container->get('request');
	$wmc = Wmc::create();
	$form = $this->container->get("form.factory")->create(new WmcLoadType(),
	    $wmc);
	$form->bindRequest($request);
	if($form->isValid())
	{
	    if($wmc->getXml() !== null)
	    {
		$file = $wmc->getXml();
		$path = $file->getPathname();
		$doc = WmcParser::loadDocument($path);
		$parser = WmcParser::getParser($doc);
		$wmc = $parser->parse();
		if(file_exists($file->getPathname())) unlink($file->getPathname());
		return new Response(json_encode(array("data" => array(round((microtime(true) * 1000)) => $wmc->getState()->getJson()))),
		    200, array(
		    'Content-Type' => 'application/json'));
	    } else
	    {
		return new Response(json_encode(array(
			"error" => 'WMC:  can not be loaded.')), 200,
		    array('Content-Type' => 'application/json'));
	    }
	}
	else
	{
	    return new Response(json_encode(array(
		    "error" => 'WMC:  can not be loaded.')), 200,
		array('Content-Type' => 'application/json'));
	}
    }

    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    public function getWmc()
    {
	$this->checkAccessLoader();
	$wmcid = $this->container->get("request")->get("wmcid", null);
	if($wmcid)
	{
	    $wmc = $this->container->get('doctrine')
		->getRepository('Mapbender\WmcBundle\Entity\Wmc')
		->find($wmcid);
	    $form = $this->container->get("form.factory")->create(new WmcType(),
		$wmc);
	    $html = $this->container->get('templating')
		->render('MapbenderWmcBundle:Wmc:form.html.twig',
		array(
		'form' => $form->createView(),
		'id' => $this->element->getEntity()->getId()));
	    return new Response($html, 200, array('Content-Type' => 'text/html'));
	}
	else
	{
	    $wmc = new Wmc();
	    $wmc->setState(new State());
	    $state = $wmc->getState();
	    $state->setServerurl($this->getBaseUrl());
	    $state->setSlug($this->application->getSlug());
	    $form = $this->container->get("form.factory")->create(new WmcType(),
		$wmc);
	    $html = $this->container->get('templating')
		->render('MapbenderWmcBundle:Wmc:form.html.twig',
		array(
		'form' => $form->createView(),
		'id' => $this->element->getEntity()->getId()));
	    return new Response($html, 200, array('Content-Type' => 'text/html'));
	}
    }

    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    public function loadWmc($wmcid)
    {
	$this->checkAccessLoader();
	if($wmcid)
	{
	    $wmc = $this->container->get('doctrine')
		->getRepository('Mapbender\WmcBundle\Entity\Wmc')
		->find($wmcid);
	    $id = $wmc->getId();
	    return new Response(json_encode(array("data" => array($id => $wmc->getState()->getJson()))),
		200, array('Content-Type' => 'application/json'));
	}
	else
	{
	    return new Response(json_encode(array("error" => 'WMC: ' . $wmcid . ' is not found')),
		200, array('Content-Type' => 'application/json'));
	}
    }

    /**
     * Returns a json encoded wmc or error if wmc is not found.
     * 
     * @param integer|string $id wmc id
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    public function removeWmc()
    {
	$this->checkAccessEditor();
	$wmcid = $this->container->get("request")->get("wmcid", null);
	$this->container->get("request")->attributes->remove("wmcid");
	if(!$wmcid)
	{
	    return new Response(json_encode(array(
		    "error" => 'Error: wmc id is not found')), 200,
		array('Content-Type' => 'application/json'));
	}
	$wmc = $this->container->get('doctrine')
	    ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
	    ->find($wmcid);
	if($wmc)
	{
	    $em = $this->container->get('doctrine')->getEntityManager();
	    $em->getConnection()->beginTransaction();
	    if($wmc->getScreenshotPath() !== null)
	    {
		$upload_directory = $this->createWmcDirs();
		if($upload_directory !== null)
		{
		    $filepath = $upload_directory . "/" . $wmc->getScreenshotPath();
		    if(file_exists($filepath)) unlink($filepath);
		}
	    }
	    $em->remove($wmc);
	    $em->flush();
	    $em->getConnection()->commit();
	    return new Response(json_encode(array(
		    "success" => "WMC: " . $wmcid . " is removed.")), 200,
		array('Content-Type' => 'application/json'));
	}
	else
	{
	    return new Response(json_encode(array(
		    "error" => "WMC: " . $wmcid . " is not found")), 200,
		array('Content-Type' => 'application/json'));
	}
    }

    /**
     * Returns a html encoded list of all wmc documents
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getWmcList()
    {
	$this->checkAccessLoader();
	$config = $this->element->getConfiguration();
	$access = true;
	if($access && $config["useEditor"] === true)
	{
	    $response = new Response();
	    $query = $this->container->get('doctrine')->getEntityManager()
		->createQuery("SELECT wmc FROM MapbenderWmcBundle:Wmc wmc"
		    . " JOIN wmc.state s Where s.slug IN (:slug)"
		    . " ORDER BY wmc.id ASC")
		->setParameter('slug', array($this->application->getSlug()));
	    $entities = $query->getResult();
	    $responseBody = $this->container->get('templating')
		->render('MapbenderWmcBundle:Wmc:list.html.twig',
		array("entities" => $entities)
	    );

	    $response->setContent($responseBody);
	    return $response;
	}
	else
	{
	    throw new AccessDeniedHttpException('You are not allowed to use this proxy without a session.');
	}
    }

    public function saveWmc()
    {
	$this->checkAccessEditor();
	$request = $this->container->get('request');
	$wmc = Wmc::create();
	$form = $this->container->get("form.factory")->create(new WmcType(),
	    $wmc);
	if($request->getMethod() === 'POST')
	{
	    $form->bindRequest($request);
	    if($form->isValid())
	    { //TODO: Is file an image (jpg/png/gif?)
		if($wmc->getId() !== null)
		{
		    $wmc = $this->container->get('doctrine')
			->getRepository('Mapbender\WmcBundle\Entity\Wmc')
			->find($wmc->getId());
		    $form = $this->container->get("form.factory")->create(new WmcType(),
			$wmc);
		    $form->bindRequest($request);
		    if(!$form->isValid())
		    {
			return new Response(json_encode(array(
				"error" => "WMC: " . $wmc->getId() . " can not be found.")), 200,
			    array('Content-Type' => 'application/json'));
		    }
		}
		$em = $this->container->get('doctrine')->getEntityManager();
		$em->getConnection()->beginTransaction();
		$em->persist($wmc);
		$em->flush();
		if($wmc->getScreenshotPath() === null)
		{
		    if($wmc->getScreenshot() !== null)
		    {
			$upload_directory = $this->createWmcDirs();
			if($upload_directory !== null)
			{
			    $dirs = $this->container->getParameter("directories");
			    $filename = sprintf('screenshot-%d.%s', $wmc->getId(),
				$wmc->getScreenshot()->guessExtension());
			    $wmc->getScreenshot()->move($upload_directory, $filename);
			    $wmc->setScreenshotPath($filename);
			    $format = $wmc->getScreenshot()->getClientMimeType();
			    $url_base = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
			    $serverurl = $url_base . "/" . $dirs["wmc"];
			    $logourl = $serverurl . "/" . $this->application->getSlug() . "/" . $filename;
			    $logoUrl = LegendUrl::create(null, null,
				    OnlineResource::create($format, $logourl));
			    $state = $wmc->getState();
			    $state->setServerurl($this->getBaseUrl());
			    $state->setSlug($this->application->getSlug());
			    $wmc->setLogourl($logoUrl);
			}
		    }
		    else
		    {
			$wmc->setScreenshotPath(null);
		    }
		    $em->persist($wmc);
		    $em->flush();
		}
		$em->getConnection()->commit();
		return new Response(json_encode(array(
			"success" => "WMC: " . $wmc->getId() . " is saved.")), 200,
		    array('Content-Type' => 'application/json'));
	    }
	    else
	    {
		return new Response(json_encode(array(
			"error" => 'WMC: ' . $wmc->getId() . ' can not be saved.')), 200,
		    array('Content-Type' => 'application/json'));
	    }
	}
    }

    protected function getBaseUrl()
    {
	$request = $this->container->get('request');
	$url_base = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
	return $url_base;
    }

    protected function createWmcDirs()
    {
	$basedir = $this->container->get('kernel')->getRootDir() . '/../web/';
	$wmc_dir = $this->container->getParameter("mapbender_core.wmc_dir");
	$dir = $basedir . $wmc_dir . "/" . $this->application->getSlug();
	if(!is_dir($dir))
	{
	    $a = mkdir($dir);
	    if($a)
	    {
		return $dir;
	    }
	    else
	    {
		return null;
	    }
	}
	else
	{
	    return $dir;
	}
    }

    protected function accessGroups($type)
    {
	$config = $this->element->getConfiguration();
	if(isset($config[$type]))
	{
	    foreach($config[$type] as $groupId)
	    {
		$groupObj = $this->container->get('doctrine')->getEntityManager()
			->getRepository('FOMUserBundle:Group')->findOneBy(array(
		    'id' => $groupId));
		$a = $groupObj->getAsRole();
		if($this->container->get("security.context")->isGranted($groupObj->getAsRole()))
		{
		    return true;
		}
	    }
	}
	return false;
    }

    protected function accessAnonymous($type)
    {
	$config = $this->element->getConfiguration();
	if(isset($config[$type]) && $config[$type] === true)
	{
	    return true;
	}
	else
	{
	    return false;
	}
    }

    protected function checkAccessEditor()
    {
	if(!$this->element->accessGroups("accessGroupsEditor") && !$this->accessAnonymous("accessEditorAnonymous"))
	{
	    return new Response(json_encode(array(
		    "error" => 'Access denied.')), 200,
		array('Content-Type' => 'application/json'));
	}
    }

    protected function checkAccessLoader()
    {
	if(!$this->element->accessGroups("accessGroupsLoader") && !$this->accessAnonymous("accessLoaderAnonymous"))
	{
	    return new Response(json_encode(array(
		    "error" => 'Access denied.')), 200,
		array('Content-Type' => 'application/json'));
	}
    }

}

