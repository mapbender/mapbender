<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\WmcBundle\Component\WmcHandler;
use Mapbender\WmcBundle\Component\WmcParser;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcLoadType;
use Symfony\Component\HttpFoundation\Response;

class WmcLoader extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
	return "WmcLoader";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
	return "WmcLoader";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
	return array("wmc", "loader");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
	return array(
	    "tooltip" => null,
	    "target" => null,
	    "components" => null,
	    "keepSources" => 'no',
	    "keepExtent" => false,
	);
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
	return 'Mapbender\WmcBundle\Element\Type\WmcLoaderAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
	return 'MapbenderWmcBundle:ElementAdmin:wmcloader.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
	return 'mapbender.mbWmcLoader';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
	$js = array(
	    'jquery.form.js',
	    'mapbender.wmchandler.js',
	    'mapbender.element.wmcloader.js'
	);
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
	$configuration = parent::getConfiguration();
	$wmcid = $this->container->get('request')->get('wmc');
	if($wmcid) $configuration["load"] = array('wmc' => $wmcid);
	return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
	$config = $this->getConfiguration();
	$html = $this->container->get('templating')
	    ->render('MapbenderWmcBundle:Element:wmcloader.html.twig',
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
	    case 'load':
		return $this->loadWmc();
		break;
	    case 'loadform':
		return $this->loadForm();
		break;
	    case 'list':
		return $this->getWmcList();
		break;
	    case 'loadxml':
		return $this->loadXml();
		break;
	    case 'wmcasxml': // TODO at client
		return $this->getWmcAsXml();
		break;
	    default:
		throw new NotFoundHttpException('No such action');
	}
    }
    
    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function loadWmc()
    {
//	if(!$this->accessGroups())
//	{
//	    return new Response(json_encode(array("error" => 'Access denied.')), 200,
//		array('Content-Type' => 'application/json'));
//	}
	$wmcid = $this->container->get('request')->get("_id", null);
	if($wmcid)
	{
	    $wmchandler = new WmcHandler($this, $this->application, $this->container);
	    $wmc = $wmchandler->getWmc($wmcid, true);
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
    
    public function loadForm()
    {
	    $wmc = new Wmc();
	    $form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
	    $html = $this->container->get('templating')
		->render('MapbenderWmcBundle:Wmc:wmcloader-form.html.twig',
		array(
		'form' => $form->createView(),
		'id' => $this->getEntity()->getId()));
	    return new Response($html, 200, array('Content-Type' => 'text/html'));
//	}
    }
    /**
     * Returns a html encoded list of all wmc documents
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    protected function getWmcList()
    {
//	if(!$this->accessGroups())
//	{
//	    return new Response(json_encode(array("error" => 'Access denied.')), 200,
//		array('Content-Type' => 'application/json'));
//	}
	$wmchandler = new WmcHandler($this, $this->application, $this->container);
	$wmclist = $wmchandler->getWmcList(true);
	$responseBody = $this->container->get('templating')
	    ->render('MapbenderWmcBundle:Wmc:wmcloader-list.html.twig',
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getWmcAsXml()
    {
	$request = $this->container->get('request');
	$wmc = Wmc::create();
	$form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
	$form->bindRequest($request);
	if($form->isValid())
	{
	    $state = $wmc->getState();
	    if($state !== null && $state->getJson() !== null)
	    {
		$state->setServerurl($this->getBaseUrl());
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

    protected function loadXml()
    {
	$request = $this->container->get('request');
	$wmc = Wmc::create();
	$form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
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
		if(file_exists($file->getPathname()))
		{
		    unlink($file->getPathname());
		}
		return new Response(json_encode(array("success" => array(round((microtime(true) * 1000)) => $wmc->getState()->getJson()))),
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
}