<?php
namespace Mapbender\CoreBundle\Element;

use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class AboutDialog extends BaseButton
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
        $defaults = array_replace(parent::getDefaultConfiguration(), array(
            "tooltip" => "About",
        ));
        unset($defaults['icon']);
        return $defaults;
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
