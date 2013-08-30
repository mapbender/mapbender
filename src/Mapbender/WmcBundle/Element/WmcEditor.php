<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity\State;
//use Mapbender\CoreBundle\Component\StateHandler;
//use Mapbender\CoreBundle\Form\Type\StateType;
//use Mapbender\WmcBundle\Component\WmcParser;
use Mapbender\WmcBundle\Component\WmcHandler;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcDeleteType;
use Mapbender\WmcBundle\Form\Type\WmcLoadType;
use Mapbender\WmcBundle\Form\Type\WmcType;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WmcEditor extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
	return "WmcEditor";
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
	return array("wmc", "editor");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
	return array(
	    "tooltip" => null,
	    "target" => null,
	    "accessGroups" => array(),
	);
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
	return 'Mapbender\WmcBundle\Element\Type\WmcEditorAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
	return 'MapbenderWmcBundle:ElementAdmin:wmceditor.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
	return 'mapbender.mbWmcEditor';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
	$js = array(
	    'jquery.form.js',
	    'mapbender.element.wmceditor.js',
	    'mapbender.wmchandler.js');
	return array(
	    'js' => $js,
	    'css' => array()
	);
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
	return parent::getConfiguration();
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
	$config = $this->getConfiguration();
	$html = $this->container->get('templating')
	    ->render('MapbenderWmcBundle:Element:wmceditor.html.twig',
	    array(
	    'id' => $this->getId(),
	    'configuration' => $config,
	    'title' => $this->getTitle()));
	return $html;
    }

    public function httpAction($action)
    {
	switch($action)
	{
	    case 'get':
		return $this->getWmc();
		break;
	    case 'list':
		return $this->getWmcList();
		break;
	    case 'confirmdelete':
		return $this->confirmDeleteWmc();
		break;
	    case 'delete':
		return $this->deleteWmc();
		break;
	    case 'save':
		return $this->saveWmc();
		break;
	    case 'load':
		return $this->loadWmc();
		break;
	    case 'public':
		return $this->setPublic();
		break;
	    default:
		throw new NotFoundHttpException('No such action');
	}
    }

    /**
     * Returns a json encoded 
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function setPublic()
    {
	if(!$this->accessGroups())
	{
	    return new Response(json_encode(array("error" => 'Access denied.')), 200,
		array('Content-Type' => 'application/json'));
	}
	$wmcid = $this->container->get("request")->get("wmcid", null);
	$enabled = $this->container->get("request")->get("public", null);
	$wmc = $this->container->get('doctrine')
		->getRepository('Mapbender\WmcBundle\Entity\Wmc')
		->find($wmcid);
	$oldenabled = $wmc->getPublic() ? "enabled" : "disabled";
	$wmc->setPublic($enabled === "enabled" ? true : false);
	$em = $this->container->get('doctrine')->getEntityManager();
	$em->persist($wmc);
	$em->flush();
	return new Response(json_encode(array(
	    "message" => "public switched",
	    "newState" => $enabled,
	    "oldState" => $oldenabled)),
		200, array('Content-Type' => 'application/json'));
    }

    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function getWmc()
    {
	if(!$this->accessGroups())
	{
	    return new Response("Access denied.", 200,
		array('Content-Type' => 'text/html'));
	}
	$wmcid = $this->container->get("request")->get("wmcid", null);
	$wmchandler = new WmcHandler($this, $this->application, $this->container);
	if($wmcid)
	{
	    $wmc = $wmchandler->getWmc($wmcid, false);
	    $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
	    $html = $this->container->get('templating')
		->render('MapbenderWmcBundle:Wmc:wmceditor-form.html.twig',
		array(
		'form' => $form->createView(),
		'id' => $this->getEntity()->getId()));
	    return new Response($html, 200, array('Content-Type' => 'text/html'));
	}
	else
	{
	    $wmc = new Wmc();
	    $wmc->setState(new State());
	    $state = $wmc->getState();
	    $state->setServerurl($wmchandler->getBaseUrl());
	    $state->setSlug($this->application->getSlug());
	    $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
	    $html = $this->container->get('templating')
		->render('MapbenderWmcBundle:Wmc:wmceditor-form.html.twig',
		array(
		'form' => $form->createView(),
		'id' => $this->getEntity()->getId()));
	    return new Response($html, 200, array('Content-Type' => 'text/html'));
	}
    }

    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function loadWmc()
    {
	if(!$this->accessGroups())
	{
	    return new Response(json_encode(array("error" => 'Access denied.')), 200,
		array('Content-Type' => 'application/json'));
	}
	$wmcid = $this->container->get('request')->get("_id", null);
	if($wmcid)
	{
	    $wmchandler = new WmcHandler($this, $this->application, $this->container);
	    $wmc = $wmchandler->getWmc($wmcid, false);
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
     * Returns a html encoded list of all wmc documents
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    protected function getWmcList()
    {
	if(!$this->accessGroups())
	{
	    return new Response(json_encode(array("error" => 'Access denied.')), 200,
		array('Content-Type' => 'application/json'));
	}
	$wmchandler = new WmcHandler($this, $this->application, $this->container);
//	$wmchandler->checkAccessEditor();
	$wmclist = $wmchandler->getWmcList(false);
	$responseBody = $this->container->get('templating')
	    ->render('MapbenderWmcBundle:Wmc:wmceditor-list.html.twig',
	    array(
	    'application' => $this->application,
	    'id' => $this->getId(),
	    'wmclist' => $wmclist)
	);
	$response = new Response();
	$response->setContent($responseBody);
	return $response;
    }

    /**
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function saveWmc()
    {
	if(!$this->accessGroups())
	{
	    return new Response(json_encode(array("error" => 'Access denied.')), 200,
		array('Content-Type' => 'application/json'));
	}
	$wmchandler = new WmcHandler($this, $this->application, $this->container);
	$request = $this->container->get('request');
	$wmc = Wmc::create();
	$form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
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
		    $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
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
			$upload_directory = $wmchandler->getWmcDir();
			if($upload_directory !== null)
			{
			    $filename = sprintf('screenshot-%d.%s', $wmc->getId(),
				$wmc->getScreenshot()->guessExtension());
			    $wmc->getScreenshot()->move($upload_directory, $filename);
			    $wmc->setScreenshotPath($filename);
			    $format = $wmc->getScreenshot()->getClientMimeType();
			    $logourl = $wmchandler->getWmcUrl($filename);
			    $logoUrl = LegendUrl::create(null, null,
				    OnlineResource::create($format, $logourl));
			    $state = $wmc->getState();
			    $state->setServerurl($wmchandler->getBaseUrl());
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

    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function confirmDeleteWmc()
    {
	if(!$this->accessGroups())
	{
	    return new Response("Access denied.", 200,
		array('Content-Type' => 'text/html'));
	}
	$wmcid = $this->container->get('request')->get("_id", null);
	if($wmcid)
	{
	    $wmchandler = new WmcHandler($this, $this->application, $this->container);
	    $wmc = $wmchandler->getWmc($wmcid, false);
	    $form = $this->container->get("form.factory")->create(new WmcDeleteType(),
		$wmc);
	    $html = $this->container->get('templating')
		->render('MapbenderWmcBundle:Wmc:deletewmc.html.twig',
		array(
		'application' => $this->application,
		'form' => $form->createView(),
		'id' => $this->getEntity()->getId(),
		'wmc' => $wmc));
	    return new Response($html, 200, array('Content-Type' => 'text/html'));
	}
	else
	{
	    return new Response('WMC is not found', 200,
		array('Content-Type' => 'text/html'));
	}
    }

    /**
     * Returns a json encoded wmc or error if wmc is not found.
     * 
     * @param integer|string $id wmc id
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function deleteWmc()
    {
	if(!$this->accessGroups())
	{
	    return new Response(json_encode(array("error" => 'Access denied.')), 200,
		array('Content-Type' => 'application/json'));
	}
	$wmc = Wmc::create();
	$form = $this->container->get("form.factory")->create(new WmcDeleteType(), $wmc);
	if($this->container->get('request')->getMethod() === 'POST')
	{
	    $form->bindRequest($this->container->get('request'));
	    if($form->isValid())
	    {
		$wmchandler = new WmcHandler($this, $this->application, $this->container);
		$wmcid = $wmc->getId();
		$wmc = $wmchandler->getWmc($wmcid, false);
		$em = $this->container->get('doctrine')->getEntityManager();
		$em->getConnection()->beginTransaction();
		if($wmc->getScreenshotPath() !== null)
		{
		    $filepath = $wmchandler->getWmcDir() . '/' . $wmc->getScreenshotPath();
		    if($filepath !== null)
		    {
			if(file_exists($filepath))
			{
			    unlink($filepath);
			}
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
			"error" => "WMC  is not found")), 200,
		    array('Content-Type' => 'application/json'));
	    }
	}
	return new Response(json_encode(array(
		"error" => "WMC cannot be removed.")), 200,
	    array('Content-Type' => 'application/json'));
    }

    /**
     * 
     * 
     * @return boolean true
     */
    protected function accessGroups()
    {
	$config = $this->getConfiguration();
	foreach($config['accessGroups'] as $groupId)
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
	return false;
    }

}

