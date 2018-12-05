<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\WmcBundle\Component\WmcHandler;
use Mapbender\WmcBundle\Component\WmcParser;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcLoadType;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WmcLoader extends Element
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
        $js = array(
            'jquery.form.js',
            'mapbender.wmchandler.js',
            'mapbender.element.wmcloader.js'
        );
        return array(
            'js' => $js,
            'css' => array(),
            'trans' => array(
                'MapbenderWmcBundle:Element:wmcloader.json.twig',
                'MapbenderWmcBundle:Element:wmchandler.json.twig')
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        if (in_array("wmcidloader", $configuration['components']) && $this->container->get('request')->get('wmcid')) {
            $configuration["load"] = array('wmcid' => $this->container->get('request')->get('wmcid'));
        } else if (in_array("wmcurlloader", $configuration['components']) && $this->container->get('request')->get('wmcurl')) {
            $configuration["load"] = array('wmcurl' => $this->container->get('request')->get('wmcurl'));
        }
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
        switch ($action) {
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
            case 'wmcfromurl': // TODO at client
                return $this->getWmcFromUrl();
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
        $config = $this->getConfiguration();
        if (in_array("wmcidloader", $config['components']) || in_array("wmclistloader", $config['components'])) {
            $wmcid = $this->container->get('request')->get("_id", null);
            $wmchandler = new WmcHandler($this, $this->application, $this->container);
            $wmc = $wmchandler->getWmc($wmcid, true);
            if ($wmc) {

                $id = $wmc->getId();
                return new Response(json_encode(array(
                        "data" => array($id => $wmc->getState()->getJson()))), 200,
                                                array('Content-Type' => 'application/json'));
            } else {
                return new Response(json_encode(array(
                        "error" => $this->trans("mb.wmc.error.wmcnotfound", array('%wmcid%' => $wmcid)))), 200,
                                                array('Content-Type' => 'application/json'));
            }
        } else {
            return new Response(json_encode(array(
                    "error" => $this->trans('mb.wmc.error.wmcidloader_notallowed'))), 200,
                                            array('Content-Type' => 'application/json'));
        }
    }

    public function loadForm()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $wmc = new Wmc();
            $form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
            $html = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Wmc:wmcloader-form.html.twig',
                         array(
                'form' => $form->createView(),
                'id' => $this->getEntity()->getId()));
            return new Response($html, 200, array('Content-Type' => 'text/html'));
        } else {
            return new Response(json_encode(array(
                    "error" => $this->trans("mb.wmc.error.wmcxmlloader_notallowed"))), 200,
                                            array('Content-Type' => 'application/json'));
        }
    }

    /**
     * Returns a html encoded list of all wmc documents
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getWmcList()
    {
        $response = new Response();
        $config = $this->getConfiguration();
        if (in_array("wmcidloader", $config['components']) || in_array("wmclistloader", $config['components'])) {
            $wmchandler = new WmcHandler($this, $this->application, $this->container);
            $wmclist = $wmchandler->getWmcList(true);
            $responseBody = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Wmc:wmcloader-list.html.twig',
                         array(
                'application' => $this->application,
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

    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getWmcAsXml()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $json = $this->container->get('request')->get("state", null);
            if ($json) {
                $wmc = Wmc::create();
                $state = $wmc->getState();
                $state->setJson(json_decode($json));
                if ($state !== null && $state->getJson() !== null) {
                    $wmchandler = new WmcHandler($this, $this->application, $this->container);
                    $state->setServerurl($wmchandler->getBaseUrl());
                    $state->setSlug($this->entity->getApplication()->getSlug());
                    $state->setTitle("Mapbender State");
                    $wmc->setWmcid(round((microtime(true) * 1000)));
                    $wmc->setState($wmchandler->unSignUrls($state));
                    $xml = $this->container->get('templating')->render(
                        'MapbenderWmcBundle:Wmc:wmc110_simple.xml.twig', array(
                        'wmc' => $wmc));
                    $response = new Response();
                    $response->setContent($xml);
                    $response->headers->set('Content-Type', 'application/xml');
                    $response->headers->set('Content-Disposition', 'attachment; filename=wmc.xml');
                    return $response;
                }
            }
        } else {
            return new Response(json_encode(array(
                    "error" => $this->trans("mb.wmc.error.wmcxmlloader_notallowed"))), 200,
                                            array('Content-Type' => 'application/json'));
        }
    }

    protected function loadXml()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $request = $this->container->get('request');
            $wmc = Wmc::create();
            $form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
            $form->bind($request);
            if ($form->isValid()) {
                if ($wmc->getXml() !== null) {
                    $file = $wmc->getXml();
                    $path = $file->getPathname();
                    $doc = WmcParser::loadDocument($path);
                    $parser = WmcParser::getParser($this->container, $doc);
                    $wmc = $parser->parse();
                    if (file_exists($file->getPathname())) {
                        unlink($file->getPathname());
                    }
                    return new Response(json_encode(array("success" => array(round((microtime(true) * 1000)) => $wmc->getState()->getJson()))),
                                                                                              200,
                                                                                              array('Content-Type' => 'application/json'));
                } else {
                    return new Response(json_encode(array(
                            "error" => $this->trans("mb.wmc.error.wmccannotbeloaded"))), 200,
                                                    array('Content-Type' => 'application/json'));
                }
            } else {
                return new Response(json_encode(array(
                        "error" => $this->trans("mb.wmc.error.wmccannotbeloaded"))), 200,
                                                array('Content-Type' => 'application/json'));
            }
        } else {
            return new Response(json_encode(array(
                    "error" => $this->trans("mb.wmc.error.wmcxmlloader_notallowed"))), 200,
                                            array('Content-Type' => 'application/json'));
        }
    }

    protected function getWmcFromUrl($url)
    {
        $config = $this->getConfiguration();
        if (in_array("wmcurlloader", $config['components']) && $this->container->get('request')->get("_url", null)) {
            $wmcurlHelp = $this->container->get('request')->get("_url");
            $rawUrl = parse_url($wmcurlHelp);
            $proxy_config = $this->container->getParameter("owsproxy.proxy");
            $proxy_query = ProxyQuery::createFromUrl(
                    $wmcurlHelp, isset($rawUrl['user']) ? $rawUrl['user'] : null,
                                       isset($rawUrl['pass']) ? $rawUrl['pass'] : null);
            $proxy = new CommonProxy($proxy_config, $proxy_query);
            $wmc = null;
            try {
                $browserResponse = $proxy->handle();
                $content = $browserResponse->getContent();
                $doc = WmcParser::createDocument($content);
                $parser = WmcParser::getParser($this->container, $doc);
                $wmc = $parser->parse();
            } catch (\Exception $e) {
                $this->get("logger")->err($e->getMessage());
                $this->get('session')->getFlashBag()->set('error', $e->getMessage());
            }
            if ($wmc) {
                return new Response(json_encode(array(
                        "success" => array(round((microtime(true) * 1000)) => $wmc->getState()->getJson()))), 200,
                                                            array('Content-Type' => 'application/json'));
            } else {
                return new Response(json_encode(array(
                        "error" => $this->trans("mb.wmc.error.wmccannotbeloaded"))), 200,
                                                array('Content-Type' => 'application/json'));
            }
        } else {
            return new Response(json_encode(array(
                    "error" => $this->trans('mb.wmc.error.wmcurlloader_notallowed'))), 200,
                                            array('Content-Type' => 'application/json'));
        }
    }

}
