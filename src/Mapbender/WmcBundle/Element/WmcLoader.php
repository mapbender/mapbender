<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\WmcBundle\Component\WmcParser;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcLoadType;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\Form\Form;
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
        } else if (in_array("wmcurlloader", $configuration['components']) && $this->container->get('request_stack')->getCurrentRequest()->get('wmcurl')) {
            $configuration["load"] = array('wmcurl' => $this->container->get('request_stack')->getCurrentRequest()->get('wmcurl'));
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

    public function loadForm()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $wmc = new Wmc();
            /** @var Form $form */
            $form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
            $html = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Wmc:wmcloader-form.html.twig',
                         array(
                'form' => $form->createView(),
                'id' => $this->getEntity()->getId()));
            return new Response($html, 200, array('Content-Type' => 'text/html'));
        } else {
            return new JsonResponse(array(
                "error" => $this->trans("mb.wmc.error.wmcxmlloader_notallowed"),
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

    /**
     *
     * @return Response
     */
    private function getWmcAsXml()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $json = $this->container->get('request_stack')->getCurrentRequest()->get("state", null);
            if ($json) {
                $wmc = Wmc::create();
                $state = $wmc->getState();
                $state->setJson(json_decode($json));
                if ($state !== null && $state->getJson() !== null) {
                    $wmchandler = $this->wmcHandlerFactory();
                    $state->setSlug($this->entity->getApplication()->getSlug());
                    $state->setTitle("Mapbender State");
                    $wmc->setWmcid(round((microtime(true) * 1000)));
                    $wmc->setState($wmchandler->unSignUrls($state));
                    $xml = $this->container->get('templating')->render(
                        'MapbenderWmcBundle:Wmc:wmc110_simple.xml.twig', array(
                        'wmc' => $wmc));
                    return new Response($xml, 200, array(
                        'Content-Type' => 'application/xml',
                        'Content-Disposition' => 'attachment; filename=wmc.xml',
                    ));
                }
            }
        } else {
            return new JsonResponse(array(
                "error" => $this->trans("mb.wmc.error.wmcxmlloader_notallowed"),
            ));
        }
    }

    protected function loadXml()
    {
        $config = $this->getConfiguration();
        if (in_array("wmcxmlloader", $config['components'])) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
            $wmc = Wmc::create();
            /** @var Form $form */
            $form = $this->container->get("form.factory")->create(new WmcLoadType(), $wmc);
            $form->submit($request);
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
                    return new JsonResponse(array(
                        "success" => array(
                            round((microtime(true) * 1000)) => $wmc->getState()->getJson(),
                        ),
                    ));
                } else {
                    return new JsonResponse(array(
                        "error" => $this->trans("mb.wmc.error.wmccannotbeloaded"),
                    ));
                }
            } else {
                return new JsonResponse(array(
                    "error" => $this->trans("mb.wmc.error.wmccannotbeloaded"),
                ));
            }
        } else {
            return new JsonResponse(array(
                "error" => $this->trans("mb.wmc.error.wmcxmlloader_notallowed"),
            ));
        }
    }

    protected function getWmcFromUrl($url)
    {
        $config = $this->getConfiguration();
        if (in_array("wmcurlloader", $config['components']) && $this->container->get('request_stack')->getCurrentRequest()->get("_url", null)) {
            $wmcurlHelp = $this->container->get('request_stack')->getCurrentRequest()->get("_url");
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
                // absolutely nothing
            }
            if ($wmc) {
                return new JsonResponse(array(
                    "success" => array(
                        round((microtime(true) * 1000)) => $wmc->getState()->getJson(),
                    ),
                ));
            } else {
                return new JsonResponse(array(
                    "error" => $this->trans("mb.wmc.error.wmccannotbeloaded"),
                ));
            }
        } else {
            return new JsonResponse(array(
                "error" => $this->trans('mb.wmc.error.wmcurlloader_notallowed'),
            ));
        }
    }

}
