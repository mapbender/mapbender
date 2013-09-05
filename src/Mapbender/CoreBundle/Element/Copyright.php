<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Copyright
 * 
 * Displays a copyright label and terms of use.
 * 
 * @author Paul Schmidt
 */
class Copyright extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
	return "Copyright";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
	return "The copyright shows a copyright label and terms of use as a dialog.";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
	return array('copyright', 'terms of use');
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
	return 'Mapbender\CoreBundle\Element\Type\CopyrightAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
	return array(
	    'js' => array(
		'mapbender.element.copyright.js',
		'@FOMCoreBundle/Resources/public/js/widgets/popup.js',
	    ),
	    'css' => array()
	);
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {

	return array(
	    'titel' => 'Copyright',
	    'autoOpen' => false,
	    'content' => null,
	);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
	return 'mapbender.mbCopyright';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
	return $this->container->get('templating')
		->render('MapbenderCoreBundle:Element:copyright.html.twig',
		    array(
		    'id' => $this->getId(),
		    'title' => $this->getTitle(),
		    'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
	return 'MapbenderCoreBundle:ElementAdmin:copyright.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
	$response = new Response();
	switch($action)
	{
	    case 'content':
		$about = $this->container->get('templating')
		    ->render('MapbenderCoreBundle:Element:copyright-content.html.twig',
		    array("configuration" => $this->getConfiguration()));
		$response->setContent($about);
		return $response;
	}
    }

}

