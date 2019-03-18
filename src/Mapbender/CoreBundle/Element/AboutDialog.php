<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class AboutDialog extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.aboutdialog.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.aboutdialog.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.aboutdialog.tag.help",
            "mb.core.aboutdialog.tag.info",
            "mb.core.aboutdialog.tag.about",
        );
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.aboutDialog.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/about_dialog.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "About",
            'label' => true,
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\AboutDialogAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:about_dialog.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbAboutDialog';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:about_dialog.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render($this->getFrontendTemplatePath(),
                    array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()));
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
                    ->render('MapbenderCoreBundle:Element:about_dialog_content.html.twig');
                $response->setContent($about);
                return $response;
        }
    }

}
