<?php

namespace Mapbender\WmcBundle\Element;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WmcLoader extends WmcBase
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.wmc.wmcloader.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.wmc.wmcloader.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("mb.wmc.suggestmap.wmc", "mb.wmc.suggestmap.loader");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => null,
            "target" => null,
            "components" => array(),
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
        return array(
            'js' => array(
                '@MapbenderWmcBundle/Resources/public/jquery.form.js',
                '@MapbenderWmcBundle/Resources/public/mapbender.wmchandler.js',
                '@MapbenderWmcBundle/Resources/public/mapbender.element.wmcloader.js'
            ),
            'css' => array(),
            'trans' => array(
                'MapbenderWmcBundle:Element:wmcloader.json.twig',
                'MapbenderWmcBundle:Element:wmchandler.json.twig',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        if (in_array("wmcidloader", $configuration['components']) && $this->container->get('request_stack')->getCurrentRequest()->get('wmcid')) {
            $configuration["load"] = array('wmcid' => $this->container->get('request_stack')->getCurrentRequest()->get('wmcid'));
        }
        return $configuration;
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderWmcBundle:Element:wmcloader.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $config = $this->getConfiguration();
        return $this->container->get('templating')->render($this->getFrontendTemplatePath(), array(
            'id' => $this->getId(),
            'configuration' => $config,
            'title' => $this->getTitle(),
        ));
    }

    public function httpAction($action)
    {
        switch ($action) {
            case 'load':
                return $this->loadWmc();
                break;
            case 'list':
                return $this->getWmcList();
                break;
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     *
     * @return JsonResponse
     */
    protected function loadWmc()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcidloader", $config['components']) || in_array("wmclistloader", $config['components'])) {
            $wmcid = $this->container->get('request_stack')->getCurrentRequest()->get("_id", null);
            $wmchandler = $this->wmcHandlerFactory();
            $wmc = $wmchandler->getWmc($wmcid, true);
            if ($wmc) {

                $id = $wmc->getId();
                return new JsonResponse(array(
                    "data" => array($id => $wmc->getState()->getJson()),
                ));
            } else {
                return new JsonResponse(array(
                    "error" => $this->trans("mb.wmc.error.wmcnotfound", array(
                        '%wmcid%' => $wmcid,
                    )),
                ));
            }
        } else {
            return new JsonResponse(array(
                "error" => $this->trans('mb.wmc.error.wmcidloader_notallowed'),
            ));
        }
    }

    /**
     * Returns a html encoded list of all wmc documents
     *
     * @return Response
     */
    protected function getWmcList()
    {
        $response = new Response();
        $config = $this->getConfiguration();
        if (in_array("wmcidloader", $config['components']) || in_array("wmclistloader", $config['components'])) {
            $wmchandler = $this->wmcHandlerFactory();
            $wmclist = $wmchandler->getWmcList(true);
            $responseBody = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Wmc:wmcloader-list.html.twig',
                         array(
                'application' => $this->getEntity()->getApplication(),
                'configuration' => $config,
                'id' => $this->getId(),
                'wmclist' => $wmclist)
            );
            $response->setContent($responseBody);
            return $response;
        } else {
            $response->setContent($this->trans("mb.wmc.error.wmclistloader_notallowed"));
            return $response;
        }
    }
}
