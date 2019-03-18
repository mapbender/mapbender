<?php

namespace Mapbender\WmcBundle\Element;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcDeleteType;
use Mapbender\WmcBundle\Form\Type\WmcType;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WmcEditor extends WmcBase
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
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderWmcBundle/Resources/public/jquery.form.js',
                '@MapbenderWmcBundle/Resources/public/mapbender.element.wmceditor.js',
                '@MapbenderWmcBundle/Resources/public/mapbender.wmchandler.js',
            ),
            'css' => array(
                '@MapbenderWmcBundle/Resources/public/sass/element/wmceditor.scss',
            ),
            'trans' => array(
                'MapbenderWmcBundle:Element:wmceditor.json.twig',
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
        if(!isset ($configuration["width"])) {
            $configuration["width"] = 480;
        }
        if(!isset ($configuration["height"])) {
            $configuration["height"] = 500;
        }
        return $configuration;
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return'MapbenderWmcBundle:Element:wmceditor.html.twig';
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
     * @return JsonResponse
     */
    protected function setPublic()
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $wmcid = $request->get("wmcid", null);
        $enabled = $request->get("public", null);
        /** @var Wmc|null $wmc */
        $wmc = $this->container->get('doctrine')
            ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
            ->find($wmcid);
        $oldenabled = $wmc->getPublic() ? "enabled" : "disabled";
        $wmc->setPublic($enabled === "enabled" ? true : false);
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        $em->persist($wmc);
        $em->flush();
        return new JsonResponse(array(
            "message" => "public switched",
            "newState" => $enabled,
            "oldState" => $oldenabled,
        ));
    }

    /**
     * Renders the wmc editor form
     *
     * @return Response
     */
    protected function getWmc()
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $wmcid = $request->get("wmcid", null);
        $wmchandler = $this->wmcHandlerFactory();
        if ($wmcid) {
            $wmc = $wmchandler->getWmc($wmcid, false);
        } else {
            $state = new State();
            $state->setSlug($this->entity->getApplication()->getSlug());
            $wmc = Wmc::create($state);
        }
        /** @var Form $form */
        $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
        $template = 'MapbenderWmcBundle:Wmc:wmceditor-form.html.twig';
        $html = $this->container->get('templating')->render($template, array(
            'form' => $form->createView(),
            'id' => $this->getEntity()->getId(),
        ));
        return new Response($html, 200, array('Content-Type' => 'text/html'));
    }

    /**
     * @return JsonResponse
     */
    protected function loadWmc()
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $wmcid = $request->get("_id", null);
        if ($wmcid) {
            $wmchandler = $this->wmcHandlerFactory();
            $wmc = $wmchandler->getWmc($wmcid, false);
            $id = $wmc->getId();
            return new JsonResponse(array(
                "data" => array(
                    $id => $wmc->getState()->getJson(),
                ),
            ));
        } else {
            return new JsonResponse(array(
                "error" => $this->trans("mb.wmc.error.wmcnotfound", array(
                    '%wmcid%' => $wmcid,
                )),
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
        $wmchandler = $this->wmcHandlerFactory();
        $wmclist = $wmchandler->getWmcList(false);
        $template = 'MapbenderWmcBundle:Wmc:wmceditor-list.html.twig';
        $responseBody = $this->container->get('templating')->render($template, array(
            'application' => $this->getEntity()->getApplication(),
            'id' => $this->getId(),
            'wmclist' => $wmclist,
        ));
        return new Response($responseBody);
    }

    /**
     *
     * @return JsonResponse
     */
    protected function saveWmc()
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $wmchandler = $this->wmcHandlerFactory();
        $wmc = Wmc::create();
        /** @var Form $form */
        $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
        if ($request->getMethod() === 'POST') {
            $form->submit($request);
            if ($form->isValid()) { //TODO: Is file an image (jpg/png/gif?)
                if ($wmc->getId() !== null) {
                    /** @var Wmc|null $wmc */
                    $wmc = $this->container->get('doctrine')
                        ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
                        ->find($wmc->getId());
                    /** @var Form $form */
                    $form = $this->container->get("form.factory")->create(new WmcType(), $wmc);
                    $form->submit($request);
                    if (!$form->isValid()) {
                        return new JsonResponse(array(
                            "error" => $this->trans("mb.wmc.error.wmcnotfound", array(
                                '%wmcid%' => '"' . $wmc->getState()->getTitle() . '" (' . $wmc->getId() . ')',
                            )),
                        ));
                    }
                }
                $wmc->setState($wmchandler->unSignUrls($wmc->getState()));
                /** @var EntityManager $em */
                $em = $this->container->get('doctrine')->getManager();
                $em->getConnection()->beginTransaction();
                $em->persist($wmc);
                $em->flush();
                if ($wmc->getScreenshotPath() === null) {
                    /** @var UploadedFile $screenshot */
                    $screenshot = $wmc->getScreenshot();
                    if ($screenshot !== null) {
                        $upload_directory = $wmchandler->getWmcDir();
                        if ($upload_directory !== null) {
                            $filename = sprintf('screenshot-%d.%s', $wmc->getId(),
                                $screenshot->guessExtension());
                            $screenshot->move($upload_directory, $filename);
                            $wmc->setScreenshotPath($filename);
                            $format = $screenshot->getClientMimeType();
                            $screenshotHref = $wmchandler->getWmcUrl($filename);
                            if ($screenshotHref) {
                                $legendOnlineResource = new OnlineResource($format, $screenshotHref);
                                $logoUrl = new LegendUrl($legendOnlineResource);
                                $wmc->setLogourl($logoUrl);
                            } else {
                                $wmc->setLogourl(null);
                            }
                            $state = $wmc->getState();
                            $state->setSlug($this->getEntity()->getApplication()->getSlug());
                        }
                    } else {
                        $wmc->setScreenshotPath(null);
                    }
                    $em->persist($wmc);
                    $em->flush();
                }
                $em->getConnection()->commit();
                return new JsonResponse(array(
                    "success" => $this->trans("mb.wmc.error.wmcsaved", array(
                        '%wmcid%' => '"' . $wmc->getState()->getTitle() . '" (' . $wmc->getId() . ')',
                    )),
                ));
            } else {
                return new JsonResponse(array(
                    "error" => $this->trans("mb.wmc.error.wmccannotbesaved", array(
                        '%wmcid%' => $wmc->getId(),
                    )),
                ));
            }
        }
    }

    /**
     * @return Response
     */
    protected function confirmDeleteWmc()
    {
        $wmcid = $this->container->get('request_stack')->getCurrentRequest()->get("_id", null);
        if ($wmcid) {
            $wmchandler = $this->wmcHandlerFactory();
            $wmc = $wmchandler->getWmc($wmcid, false);
            /** @var Form $form */
            $form = $this->container->get("form.factory")->create(new WmcDeleteType(), $wmc);
            $template = 'MapbenderWmcBundle:Wmc:deletewmc.html.twig';
            $html = $this->container->get('templating')->render($template, array(
                'application' => $this->getEntity()->getApplication(),
                'form' => $form->createView(),
                'id' => $this->getEntity()->getId(),
                'wmc' => $wmc,
            ));
            return new Response($html, 200, array('Content-Type' => 'text/html'));
        } else {
            return new Response($this->trans("mb.wmc.error.wmcnotfound", array('%wmcid%' => '')), 200,
                array('Content-Type' => 'text/html'));
        }
    }

    /**
     * @return JsonResponse
     */
    protected function deleteWmc()
    {
        $wmc = Wmc::create();
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        /** @var Form $form */
        $form = $this->container->get("form.factory")->create(new WmcDeleteType(), $wmc);
        if ($request->getMethod() === 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $wmchandler = $this->wmcHandlerFactory();
                $wmcid = $wmc->getId();
                $wmc = $wmchandler->getWmc($wmcid, false);
                /** @var EntityManager $em */
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
                return new JsonResponse(array(
                    "success" => $this->trans("mb.wmc.error.wmcremoved", array(
                        '%wmcid%' => '"' . $wmc->getState()->getTitle() . '" (' . $wmcid . ")",
                    )),
                ));
            } else {
                return new JsonResponse(array(
                    "error" => $this->trans("mb.wmc.error.wmcnotfound", array(
                        '%wmcid%' => '',
                    )),
                ));
            }
        }
        return new JsonResponse(array(
            "error" => $this->trans("mb.wmc.error.wmccannotberemoved"),
        ));
    }
}
