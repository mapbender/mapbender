<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\WmcBundle\Component\WmcHandler;
use Symfony\Component\HttpFoundation\Response;

class SuggestMap extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
	return "SuggestMap";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
	return "SuggestMap";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
	return array("suggest", "map");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
	return array(
	    "tooltip" => null,
	    "target" => null,
	    "receiver" => array('email'),
	);
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
	return 'Mapbender\WmcBundle\Element\Type\SuggestMapAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
	return 'MapbenderWmcBundle:ElementAdmin:suggestmap.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
	return 'mapbender.mbSuggestMap';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
	$js = array(
	    'mapbender.element.suggestmap.js',
	    '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
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
	$stateid = $this->container->get('request')->get('state');
	if($stateid) $configuration["load"] = array('state' => $stateid);
	return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
	$config = $this->getConfiguration();
	$html = $this->container->get('templating')
	    ->render('MapbenderWmcBundle:Element:suggestmap.html.twig',
	    array(
	    'id' => $this->getId(),
	    'configuration' => $config,
	    'title' => $this->getTitle(),
	));
	return $html;
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
	$session = $this->container->get("session");

	if($session->get("proxyAllowed", false) !== true)
	{
	    throw new AccessDeniedHttpException('You are not allowed to use this proxy without a session.');
	}
	switch($action)
	{
	    case 'load':
		$id = $this->container->get('request')->get("_id", null);
		return $this->loadState($id);
		break;
	    case 'state':
		return $this->saveState();
		break;
	    case 'content':
		return $this->getContent();
		break;
	    default:
		throw new NotFoundHttpException('No such action');
	}
    }
    
    protected function getContent(){
	$config = $this->getConfiguration();
	$html = $this->container->get('templating')
	    ->render('MapbenderWmcBundle:Element:suggestmap-content.html.twig',
	    array(
	    'id' => $this->getId(),
	    'configuration' => $config,
	    'title' => $this->getTitle(),
	));
	return new Response($html, 200, array('Content-Type' => 'text/html'));
    }

    /**
     * Returns a json encoded state
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function loadState($stateid)
    {
	$wmchandler = new WmcHandler($this, $this->application, $this->container);
	$state = $wmchandler->findState($stateid);
	if($state)
	{
	    $id = $state->getId();
	    return new Response(json_encode(array("data" => array($id => $state->getJson()))),
		200, array('Content-Type' => 'application/json'));
	}
	else
	{
	    return new Response(json_encode(array("error" => 'State: ' . $stateid . ' is not found')),
		200, array('Content-Type' => 'application/json'));
	}
    }

    /**
     * Saves the mapbender state.
     * 
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function saveState()
    {
	$wmchandler = new WmcHandler($this, $this->application, $this->container);
	$json = $this->container->get('request')->get("state", null);
	$state = $wmchandler->saveState($json);
	if($state !== null)
	{
	    return new Response(json_encode(array(
		    "id" => $state->getId())), 200,
		array('Content-Type' => 'application/json'));
	}
	else
	{
	    return new Response(json_encode(array(
		    "error" => 'State can not be saved.')), 200,
		array('Content-Type' => 'application/json'));
	}
    }

}

