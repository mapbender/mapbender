<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcType;
//use Mapbender\CoreBundle\Component\ElementInterface;
//use Mapbender\WmcBundle\Component\WmcParser;
//use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

//use Symfony\Component\Serializer\Serializer;
//use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
//use Symfony\Component\Serializer\Encoder\JsonEncoder;

class WmcEditor extends Element
{
//    /**
//     * @inheritdoc
//     */
//    public function __construct($application, $container, $entity)
//    {
//
//    }

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "WmcEditor";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "WMC Editor";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("wmc", "editor");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => null,
            "target" => null,
//            "geoportalurl" => null
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
                'mapbender.element.wmceditor.js'
            ),
            'css' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
//        $configuration = parent::getConfiguration();
//        $opts = $configuration;
//        $opts['text'] = $this->getTitle();
//        $opts['ll'] = $this->lebenslagen_leistungen;
//        $opts['init'] = 'mbWmcEditor';
//        $opts['selectedgroup'] = array();
//        return $opts;
        return parent::getConfiguration();
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $wmc = Wmc::create();
        $form = $this->container->get("form.factory")->create(new WmcType(),
                                                              $wmc);
        $html = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Element:wmceditor.html.twig',
                         array(
            'id' => $this->getId(),
            'configuration' => $this->getConfiguration(),
            'title' => $this->getTitle(),
            'form' => $form->createView(),
                ));
        return $html;
    }

    public function httpAction($action)
    {
        switch($action)
        {
            case 'save':

                return $this->save();
                break;
            case 'update':
                $tkid = $this->get("request")->get("tkid", null);
                $this->get("request")->attributes->remove("tkid");
                return $this->save($tkid);
                break;
            case 'get':
                $tkid = $this->get("request")->get("tkid", null);
                return $this->getTk($tkid);
                break;
            case 'index':
                return $this->index();
                break;
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    protected function getTk($id)
    {
        $themenkarte = $this
                ->get('doctrine')
                ->getRepository('Bkg\GeoportalBundle\Entity\Themenkarte')
                ->find($id);
        $form = $this->getForm($themenkarte);
        $responseBody = $this
                ->get('templating')
                ->render("BkgGeoportalBundle:Themenkarte:edit.html.twig",
                         array(
            "edit_form" => $form->createView(),
            "entity" => $themenkarte));
        $response = new Response();
        $response->setContent($responseBody);
        return $response;
    }

    protected function index()
    {
        $response = new Response();
        $entities = $this
                ->get('doctrine')
                ->getRepository('Bkg\GeoportalBundle\Entity\Themenkarte')
                ->findAll();
        $responseBody = $this
                ->get('templating')
                ->render('BkgGeoportalBundle:Themenkarte:index.html.twig',
                         array("entities" => $entities)
        );

        $response->setContent($responseBody);
        return $response;
    }

    private function save($id = null)
    {
        $response = new Response();
        $request = $this->container->get('request');

        if($id)
        {
            $wmc = $this->container->get('doctrine')
                    ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
                    ->find($id);
        } else
        {
            $wmc = Wmc::create();
        }
        $form = $this->container->get("form.factory")->create(new WmcType(),
                                                              $wmc);
        if($request->getMethod() === 'POST')
        {
            $form->bindRequest($request);
            if($form->isValid())
            { //TODO: Is file an image (jpg/png/gif?)
                $em = $this->container->get('doctrine')->getEntityManager();
                $em->persist($wmc);
                $em->flush();


                if(!$wmc->getScreenshotPath())
                {
                    $upload_directory = $this->getParameter("themenkartenscreenshot_directory");
                    $filename = sprintf('screenshot-%d.%s',
                                        $themenkarte->getId(),
                                        $themenkarte->getScreenshot()->guessExtension());
                    $themenkarte->getScreenshot()
                            ->move($upload_directory, $filename);
                    $themenkarte->setScreenshotPath($filename);
                }

                $patern = array('/"?minScale"?:\s?null\s?,?/',
                    '/"?maxScale"?:\s?null\s?,?/');
                $rplmt = array("", "");
                $services = preg_replace($patern, $rplmt,
                                         $themenkarte->getServices());
                $themenkarte->setServices($services);

                $em->persist($themenkarte);
                $em->flush();

                $path = $this->getParameter("themenkartenwmc_directory");
                $path .= "/themenkarte_" . $themenkarte->getId() . "wmc.xml";
                file_put_contents($path, $themenkarte->getWmc() ? : "");

                $response->setContent($themenkarte->getId());
            } else
            {
                $response->setContent('error');
            }
        }
        return $response;
    }

    public function generateMetadata($themenkarte)
    {
        return $this->get('templating')->render('BkgGeoportalBundle:Element:themenkarteneditor_wmcmetadata.html.twig',
                                                array("themenkarte" => $themenkarte)
        );
    }

}

