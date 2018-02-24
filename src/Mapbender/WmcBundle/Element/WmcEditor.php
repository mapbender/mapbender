<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\WmcBundle\Component\WmcHandler;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcDeleteType;
use Mapbender\WmcBundle\Form\Type\WmcType;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WmcEditor extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.wmc.wmceditor.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.wmc.wmceditor.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("mb.wmc.suggestmap.wmc", "mb.wmc.suggestmap.editor");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => null,
            "target" => null,
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
    static public function listAssets()
    {
        $js = array(
            'jquery.form.js',
            'mapbender.element.wmceditor.js',
            'mapbender.wmchandler.js');
        return array(
            'js' => $js,
            'css' => array(
                'sass/element/wmceditor.scss'),
            'trans' => array(
                'MapbenderWmcBundle:Element:wmceditor.json.twig',
                'MapbenderWmcBundle:Element:wmchandler.json.twig')
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        if(!isset ($configuration["width"])) {
            $configuration["width"] = 480;
        }
        if(!isset ($configuration["height"])) {
            $configuration["height"] = 500;
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
            ->render('MapbenderWmcBundle:Element:wmceditor.html.twig',
            array(
            'id' => $this->getId(),
            'configuration' => $config,
            'title' => $this->getTitle()));
        return $html;
    }

    public function httpAction($action)
    {
        switch ($action) {
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
        $wmcid = $this->container->get("request")->get("wmcid", null);
        $enabled = $this->container->get("request")->get("public", null);
        $wmc = $this->container->get('doctrine')
            ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
            ->find($wmcid);
        $oldenabled = $wmc->getPublic() ? "enabled" : "disabled";
        $wmc->setPublic($enabled === "enabled" ? true : false);
        $em = $this->container->get('doctrine')->getManager();
        $em->persist($wmc);
        $em->flush();
        return new Response(json_encode(array(
                "message" => "public switched",
                "newState" => $enabled,
                "oldState" => $oldenabled)), 200, array('Content-Type' => 'application/json'));
    }

    /**
     * Returns a json encoded or html form wmc or error if wmc is not found.
     *
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function getWmc()
    {
        $wmcid = $this->container->get("request")->get("wmcid", null);
        $wmchandler = new WmcHandler($this, $this->application, $this->container);
        if ($wmcid) {
            $wmc = $wmchandler->getWmc($wmcid, false);
            $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
            $html = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Wmc:wmceditor-form.html.twig',
                array(
                'form' => $form->createView(),
                'id' => $this->getEntity()->getId()));
            return new Response($html, 200, array('Content-Type' => 'text/html'));
        } else {
            $wmc = new Wmc();
            $wmc->setState(new State());
            $state = $wmc->getState();
            $state->setServerurl($wmchandler->getBaseUrl());
            $state->setSlug($this->application->getSlug());
            $state = $wmchandler->signUrls($state);
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
        $wmcid = $this->container->get('request')->get("_id", null);
        if ($wmcid) {
            $wmchandler = new WmcHandler($this, $this->application, $this->container);
            $wmc = $wmchandler->getWmc($wmcid, false);
            $id = $wmc->getId();
            return new Response(json_encode(array("data" => array($id => $wmc->getState()->getJson()))), 200,
                array('Content-Type' => 'application/json'));
        } else {
            return new Response(json_encode(array("error" => $this->trans("mb.wmc.error.wmcnotfound",
                        array('%wmcid%' => $wmcid)))), 200, array('Content-Type' => 'application/json'));
        }
    }

    /**
     * Returns a html encoded list of all wmc documents
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getWmcList()
    {
        $wmchandler = new WmcHandler($this, $this->application, $this->container);
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
        $wmchandler = new WmcHandler($this, $this->application, $this->container);
        $request = $this->container->get('request');
        $wmc = Wmc::create();
        $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
        if ($request->getMethod() === 'POST') {
            $form->bind($request);
            if ($form->isValid()) { //TODO: Is file an image (jpg/png/gif?)
                if ($wmc->getId() !== null) {
                    $wmc = $this->container->get('doctrine')
                        ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
                        ->find($wmc->getId());
                    $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
                    $form->bind($request);
                    if (!$form->isValid()) {
                        return new Response(json_encode(array(
                                "error" => $this->trans("mb.wmc.error.wmcnotfound",
                                    array('%wmcid%' => '"' . $wmc->getState()->getTitle() . '" (' . $wmc->getId() . ')')))),
                            200, array('Content-Type' => 'application/json'));
                    }
                }
                $wmc->setState($wmchandler->unSignUrls($wmc->getState()));
                $em = $this->container->get('doctrine')->getManager();
                $em->getConnection()->beginTransaction();
                $em->persist($wmc);
                $em->flush();
                if ($wmc->getScreenshotPath() === null) {
                    if ($wmc->getScreenshot() !== null) {
                        $upload_directory = $wmchandler->getWmcDir();
                        if ($upload_directory !== null) {
                            $filename = sprintf('screenshot-%d.%s', $wmc->getId(),
                                $wmc->getScreenshot()->guessExtension());
                            $wmc->getScreenshot()->move($upload_directory, $filename);
                            $wmc->setScreenshotPath($filename);
                            $format = $wmc->getScreenshot()->getClientMimeType();
                            $screenshotHref = $wmchandler->getWmcUrl($filename);
                            if ($screenshotHref) {
                                $legendOnlineResource = new OnlineResource($format, $screenshotHref);
                                $logoUrl = new LegendUrl($legendOnlineResource);
                                $wmc->setLogourl($logoUrl);
                            } else {
                                $wmc->setLogourl(null);
                            }
                            $state = $wmc->getState();
                            $state->setServerurl($wmchandler->getBaseUrl());
                            $state->setSlug($this->application->getSlug());
                        }
                    } else {
                        $wmc->setScreenshotPath(null);
                    }
                    $em->persist($wmc);
                    $em->flush();
                }
                $em->getConnection()->commit();
                return new Response(json_encode(array(
                        "success" => $this->trans("mb.wmc.error.wmcsaved",
                            array('%wmcid%' => '"' . $wmc->getState()->getTitle() . '" (' . $wmc->getId() . ')')))),
                    200, array('Content-Type' => 'application/json'));
            } else {
                return new Response(json_encode(array(
                        "error" => $this->trans("mb.wmc.error.wmccannotbesaved", array('%wmcid%' => $wmc->getId())))),
                    200, array('Content-Type' => 'application/json'));
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
        $wmcid = $this->container->get('request')->get("_id", null);
        if ($wmcid) {
            $wmchandler = new WmcHandler($this, $this->application, $this->container);
            $wmc = $wmchandler->getWmc($wmcid, false);
            $form = $this->container->get("form.factory")->create(new WmcDeleteType(), $wmc);
            $html = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Wmc:deletewmc.html.twig',
                array(
                'application' => $this->application,
                'form' => $form->createView(),
                'id' => $this->getEntity()->getId(),
                'wmc' => $wmc));
            return new Response($html, 200, array('Content-Type' => 'text/html'));
        } else {
            return new Response($this->trans("mb.wmc.error.wmcnotfound", array('%wmcid%' => '')), 200,
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
        $wmc = Wmc::create();
        $form = $this->container->get("form.factory")->create(new WmcDeleteType(), $wmc);
        if ($this->container->get('request')->getMethod() === 'POST') {
            $form->bind($this->container->get('request'));
            if ($form->isValid()) {
                $wmchandler = new WmcHandler($this, $this->application, $this->container);
                $wmcid = $wmc->getId();
                $wmc = $wmchandler->getWmc($wmcid, false);
                $em = $this->container->get('doctrine')->getManager();
                $em->getConnection()->beginTransaction();
                if ($wmc->getScreenshotPath() !== null) {
                    $filepath = $wmchandler->getWmcDir() . '/' . $wmc->getScreenshotPath();
                    if ($filepath !== null) {
                        if (file_exists($filepath)) {
                            unlink($filepath);
                        }
                    }
                }
                $em->remove($wmc);
                $em->flush();
                $em->getConnection()->commit();
                return new Response(json_encode(array(
                        "success" => $this->trans("mb.wmc.error.wmcremoved",
                            array('%wmcid%' => '"' . $wmc->getState()->getTitle() . '" (' . $wmcid . ")")))), 200,
                    array('Content-Type' => 'application/json'));
            } else {
                return new Response(json_encode(array(
                        "error" => $this->trans("mb.wmc.error.wmcnotfound", array('%wmcid%' => '')))), 200,
                    array('Content-Type' => 'application/json'));
            }
        }
        return new Response(json_encode(array(
                "error" => $this->trans("mb.wmc.error.wmccannotberemoved"))), 200,
            array('Content-Type' => 'application/json'));
    }

}
