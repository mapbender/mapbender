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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WmcHandler
{

    public static $WMC_DIR = "wmc";
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
//
//    private function getWmcAsXml()
//    {
//	$request = $this->container->get('request');
//	$wmc = Wmc::create();
//	$form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
//	$form->bindRequest($request);
//	if($form->isValid())
//	{ //TODO: Is file an image (jpg/png/gif?)
//	    $state = $wmc->getState();
//	    if($state !== null && $state->getJson() !== null)
//	    {
//		$state->setServerurl($this->element->getBaseUrl());
//		$state->setSlug($this->application->getSlug());
//		$state->setTitle("Mapbender State");
//		$wmc->setWmcid(round((microtime(true) * 1000)));
//		$xml = $this->container->get('templating')
//		    ->render('MapbenderWmcBundle:Wmc:wmc110_simple.xml.twig',
//		    array(
//		    'wmc' => $wmc));
//		$response = new Response();
//		$response->setContent($xml);
//		$response->headers->set('Content-Type', 'application/xml');
//		$response->headers->set('Content-Disposition', 'attachment; filename=wmc.xml');
//		return $response;
//	    }
//	}
//	return new Response(json_encode(array(
//		"error" => 'WMC:  can not be loaded.')), 200,
//	    array('Content-Type' => 'application/json'));
//    }
//
//    public function loadXml()
//    {
//	$request = $this->container->get('request');
//	$wmc = Wmc::create();
//	$form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
//	$form->bindRequest($request);
//	if($form->isValid())
//	{
//	    if($wmc->getXml() !== null)
//	    {
//		$file = $wmc->getXml();
//		$path = $file->getPathname();
//		$doc = WmcParser::loadDocument($path);
//		$parser = WmcParser::getParser($doc);
//		$wmc = $parser->parse();
//		if(file_exists($file->getPathname())) unlink($file->getPathname());
//		return new Response(json_encode(array("data" => array(round((microtime(true) * 1000)) => $wmc->getState()->getJson()))),
//		    200, array(
//		    'Content-Type' => 'application/json'));
//	    } else
//	    {
//		return new Response(json_encode(array(
//			"error" => 'WMC:  can not be loaded.')), 200,
//		    array('Content-Type' => 'application/json'));
//	    }
//	}
//	else
//	{
//	    return new Response(json_encode(array(
//		    "error" => 'WMC:  can not be loaded.')), 200,
//		array('Content-Type' => 'application/json'));
//	}
//    }

    /**
     * Returns a wmc.
     * @param integer $wmcid a Wmc id
     * 
     * @return Wmc or null.
     */
    public function getWmc($wmcid, $onlyPublic = TRUE)
    {
	$query = $this->container->get('doctrine')->getEntityManager()
	    ->createQuery("SELECT wmc FROM MapbenderWmcBundle:Wmc wmc"
		. " JOIN wmc.state s Where s.slug IN (:slug)"
		. " AND wmc.id=:wmcid"
		. ($onlyPublic === TRUE ? " AND wmc.public = 'true'" : "")
		. " ORDER BY wmc.id ASC")
	    ->setParameter('slug', array($this->application->getSlug()))
	    ->setParameter('wmcid', $wmcid);
	$wmc = $query->getResult();
	if($wmc && count($wmc) === 1) return $wmc[0];
	else return null;
    }

//
//    /**
//     * Returns a json encoded or html form wmc or error if wmc is not found.
//     * 
//     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
//     */
//    public function loadWmc($wmcid)
//    {
//	$this->checkAccessLoader();
//	if($wmcid)
//	{
//	    $wmc = $this->container->get('doctrine')
//		->getRepository('Mapbender\WmcBundle\Entity\Wmc')
//		->find($wmcid);
//	    $id = $wmc->getId();
//	    return new Response(json_encode(array("data" => array($id => $wmc->getState()->getJson()))),
//		200, array('Content-Type' => 'application/json'));
//	}
//	else
//	{
//	    return new Response(json_encode(array("error" => 'WMC: ' . $wmcid . ' is not found')),
//		200, array('Content-Type' => 'application/json'));
//	}
//    }
//
//    /**
//     * Returns a json encoded wmc or error if wmc is not found.
//     * 
//     * @param integer|string $id wmc id
//     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
//     */
//    public function removeWmc()
//    {
//	$this->checkAccessEditor();
//	$wmcid = $this->container->get("request")->get("wmcid", null);
//	$this->container->get("request")->attributes->remove("wmcid");
//	if(!$wmcid)
//	{
//	    return new Response(json_encode(array(
//		    "error" => 'Error: wmc id is not found')), 200,
//		array('Content-Type' => 'application/json'));
//	}
//	$wmc = $this->container->get('doctrine')
//	    ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
//	    ->find($wmcid);
//	if($wmc)
//	{
//	    $em = $this->container->get('doctrine')->getEntityManager();
//	    $em->getConnection()->beginTransaction();
//	    if($wmc->getScreenshotPath() !== null)
//	    {
//		$upload_directory = $this->getWmcDir();
//		if($upload_directory !== null)
//		{
//		    $filepath = $upload_directory . "/" . $wmc->getScreenshotPath();
//		    if(file_exists($filepath)) unlink($filepath);
//		}
//	    }
//	    $em->remove($wmc);
//	    $em->flush();
//	    $em->getConnection()->commit();
//	    return new Response(json_encode(array(
//		    "success" => "WMC: " . $wmcid . " is removed.")), 200,
//		array('Content-Type' => 'application/json'));
//	}
//	else
//	{
//	    return new Response(json_encode(array(
//		    "error" => "WMC: " . $wmcid . " is not found")), 200,
//		array('Content-Type' => 'application/json'));
//	}
//    }

    /**
     * Returns a wmc list
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getWmcList($onlyPublic = true)
    {
	$query = $this->container->get('doctrine')->getEntityManager()
	    ->createQuery("SELECT wmc FROM MapbenderWmcBundle:Wmc wmc"
		. " JOIN wmc.state s Where s.slug IN (:slug)"
		. ($onlyPublic === TRUE ? " AND wmc.public='true'" : "")
		. " ORDER BY wmc.id ASC")
	    ->setParameter('slug', array($this->application->getSlug()));
	return $query->getResult();
    }

//    public function saveWmc()
//    {
//	$this->checkAccessEditor();
//	$request = $this->container->get('request');
//	$wmc = Wmc::create();
//	$form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
//	if($request->getMethod() === 'POST')
//	{
//	    $form->bindRequest($request);
//	    if($form->isValid())
//	    { //TODO: Is file an image (jpg/png/gif?)
//		if($wmc->getId() !== null)
//		{
//		    $wmc = $this->container->get('doctrine')
//			->getRepository('Mapbender\WmcBundle\Entity\Wmc')
//			->find($wmc->getId());
//		    $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
//		    $form->bindRequest($request);
//		    if(!$form->isValid())
//		    {
//			return new Response(json_encode(array(
//				"error" => "WMC: " . $wmc->getId() . " can not be found.")), 200,
//			    array('Content-Type' => 'application/json'));
//		    }
//		}
//		$em = $this->container->get('doctrine')->getEntityManager();
//		$em->getConnection()->beginTransaction();
//		$em->persist($wmc);
//		$em->flush();
//		if($wmc->getScreenshotPath() === null)
//		{
//		    if($wmc->getScreenshot() !== null)
//		    {
//			$upload_directory = $this->getWmcDir();
//			if($upload_directory !== null)
//			{
//			    $dirs = $this->container->getParameter("directories");
//			    $filename = sprintf('screenshot-%d.%s', $wmc->getId(),
//				$wmc->getScreenshot()->guessExtension());
//			    $wmc->getScreenshot()->move($upload_directory, $filename);
//			    $wmc->setScreenshotPath($filename);
//			    $format = $wmc->getScreenshot()->getClientMimeType();
//			    $url_base = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
//			    $serverurl = $url_base . "/" . $dirs["wmc"];
//			    $logourl = $serverurl . "/" . $this->application->getSlug() . "/" . $filename;
//			    $logoUrl = LegendUrl::create(null, null,
//				    OnlineResource::create($format, $logourl));
//			    $state = $wmc->getState();
//			    $state->setServerurl($this->getBaseUrl());
//			    $state->setSlug($this->application->getSlug());
//			    $wmc->setLogourl($logoUrl);
//			}
//		    }
//		    else
//		    {
//			$wmc->setScreenshotPath(null);
//		    }
//		    $em->persist($wmc);
//		    $em->flush();
//		}
//		$em->getConnection()->commit();
//		return new Response(json_encode(array(
//			"success" => "WMC: " . $wmc->getId() . " is saved.")), 200,
//		    array('Content-Type' => 'application/json'));
//	    }
//	    else
//	    {
//		return new Response(json_encode(array(
//			"error" => 'WMC: ' . $wmc->getId() . ' can not be saved.')), 200,
//		    array('Content-Type' => 'application/json'));
//	    }
//	}
//    }
    /**
     * Gets a base url
     * 
     * @return string a base url
     */
    public function getBaseUrl()
    {
	$request = $this->container->get('request');
	$url_base = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
	return $url_base;
    }

    /**
     * Gets a url to wmc directory or to file with "$filename
     * 
     * @param string $filename
     * @return string a url to wmc directory or to file with "$filename" 
     */
    public function getWmcUrl($filename = null)
    {
	$url_base = $this->getBaseUrl() . '/'
	    . $this->container->getParameter("mapbender.uploads_dir")
	    . "/" . $this->application->getSlug() . '/' . WmcHandler::$WMC_DIR; ;
	if($filename !== null)
	{
	    return $url_base . '/' . $filename;
	}
	else
	{
	    return $url_base;
	}
    }

    /**
     * Gets a path to wmc directory
     * 
     * @return string|null path to wmc directory or null
     */
    public function getWmcDir()
    {
	$wmc_dir = $this->container->get('kernel')->getRootDir() . '/../web/'
	    . $this->container->getParameter("mapbender.uploads_dir")
	    . "/" . $this->application->getSlug() . '/' . WmcHandler::$WMC_DIR;
	if(!is_dir($wmc_dir))
	{
	    if(mkdir($wmc_dir))
	    {
		return $wmc_dir;
	    }
	    else
	    {
		return null;
	    }
	}
	else
	{
	    return $wmc_dir;
	}
    }
//
//    public function accessGroups($type)
//    {
//	$config = $this->element->getConfiguration();
//	if(isset($config[$type]))
//	{
//	    foreach($config[$type] as $groupId)
//	    {
//		$groupObj = $this->container->get('doctrine')->getEntityManager()
//			->getRepository('FOMUserBundle:Group')->findOneBy(array(
//		    'id' => $groupId));
//		$a = $groupObj->getAsRole();
//		if($this->container->get("security.context")->isGranted($groupObj->getAsRole()))
//		{
//		    return true;
//		}
//	    }
//	}
//	return false;
//    }
//
//    public function accessAnonymous($type)
//    {
//	$config = $this->element->getConfiguration();
//	if(isset($config[$type]) && $config[$type] === true)
//	{
//	    return true;
//	}
//	else
//	{
//	    return false;
//	}
//    }
//
//    public function checkAccessEditor($contenttype = 'application/json')
//    {
//	if(!$this->accessGroups("accessGroups"))
//	{
//	    throw new AccessDeniedHttpException('You are not allowed to use this action.');
////	    if($contenttype === 'application/json')
////	    {
////		return new Response(json_encode(array(
////			"error" => 'Access denied.')), 200,
////		    array('Content-Type' => 'application/json'));
////	    }
////	    else
////	    {
////		return new Response(json_encode(array(
////			"error" => 'Access denied.')), 200,
////		    array('Content-Type' => 'text/html'));
////	    }
//	}
//    }
//
//    public function checkAccessLoader()
//    {
//	if(!$this->element->accessGroups("accessGroupsLoader") && !$this->accessAnonymous("accessLoaderAnonymous"))
//	{
//	    return new Response(json_encode(array(
//		    "error" => 'Access denied.')), 200,
//		array('Content-Type' => 'application/json'));
//	}
//    }

}

