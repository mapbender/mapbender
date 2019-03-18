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
        return "mb.core.copyright.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.copyright.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            'mb.core.copyright.tag.copyright',
            "mb.core.copyright.tag.dialog");
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
                '@MapbenderCoreBundle/Resources/public/mapbender.element.copyright.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/copyright.scss',
            ),
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
            'popupWidth'    => 300,
            'popupHeight'   => 170,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbCopyright';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:copyright.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render($this->getFrontendTemplatePath(), array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration(),
        ));
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
        switch ($action) {
            case 'content':
                $about = $this->container->get('templating')
                    ->render('MapbenderCoreBundle:Element:copyright-content.html.twig',
                    array("configuration" => $this->getConfiguration()));
                $response->setContent($about);
                return $response;
        }
    }

}
