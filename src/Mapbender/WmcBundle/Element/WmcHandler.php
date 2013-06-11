<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmcBundle\Form\Type\WmcType;
use Symfony\Component\HttpFoundation\Response;

class WmcHandler extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "WmcHandler";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Wmc Handler";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("wmc", "handler");
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
        return 'Mapbender\WmcBundle\Element\Type\WmcHandlerAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbWmcHandler';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.wmchandler.js'),
            'css' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        return parent::getConfiguration();
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $html = $this->container->get('templating')
                ->render('MapbenderWmcBundle:Element:wmchandler.html.twig',
                         array(
            'id' => $this->getId(),
            'configuration' => $this->getConfiguration(),
            'title' => $this->getTitle()));
        return $html;
    }

    public function httpAction($action)
    {
        $session = $this->container->get("session");

        if($session->get("proxyAllowed", false) !== true)
        {
            throw new AccessDeniedHttpException('You are not allowed to use this proxy without a session.');
        }
        switch($action)
        {
            case 'loadfromid':
                $wmcid = $this->container->get("request")->get("wmcid", null);
                return $this->getWmc($wmcid);
                break;
//            case 'delete':
//                $wmcid = $this->container->get("request")->get("wmcid", null);
//                $this->container->get("request")->attributes->remove("wmcid");
//                return $this->delete($wmcid);
//                break;
//            case 'update':
//                $tkid = $this->get("request")->get("tkid", null);
//                $this->get("request")->attributes->remove("tkid");
//                return $this->save($tkid);
//                break;
//            case 'get':
//                $tkid = $this->get("request")->get("tkid", null);
//                return $this->getWmc($tkid);
//                break;
//            case 'index':
//                return $this->index();
//                break;
//            case 'wmcasxml':
//                return $this->getWmcAsXml();
//                break;
//            case 'wmcasjson':
//                return $this->getWmcAsJson();
//                break;
            default:
                throw new NotFoundHttpException('No such action');
        }
    }
    
    
//    
//    private function getWmcAsJson(){
//        $id = $this->container->get('request')->get('id');
//        $wmc = $this->container->get('doctrine')->getRepository('LippeGeoportalBundle:Wmc')
//                ->find($id);
//        $doc = WmcParser::createDocument($wmc->getDocument());
//        $wmcp = WmcParser::getParser($doc);
//        $wmc_temp = $wmcp->parse();
//        $result = array(
//            "json" => $wmc_temp->getState()->getJson()
//        );
//        $response = new Response();
//        $response->setContent(json_encode($result));
//        $response->headers->set('Content-Type', 'application/json');
//        return $response;
//    }
//    
//    private function getWmcAsXml(){
//        $id = $this->container->get('request')->get('id');
//        $wmc = $this->container->get('doctrine')->getRepository('LippeGeoportalBundle:Wmc')
//                ->find($id);
//        
//        $wmcxml = $this->container->get('templating')
//                        ->render('MapbenderWmcBundle:Wmc:wmc110.xml.twig',
//                                 array(
//                            'wmc' => $wmc));
//        $result = array(
//            "xml" => $wmc->getId()
//        );
//        $response = new Response();
//        $response->setContent(json_encode($result));
//        $response->headers->set('Content-Type', 'application/json');
//        return $response;
//    }

    /**
     * Returns a json encoded wmc or error if wmc is not found.
     * 
     * @param integer|string $id wmc id
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function getWmc($id)
    {
        if($id)
        {
            $wmc = $this->container->get('doctrine')
                    ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
                    ->find($id);
            $stateJson = $wmc->getState()->getJson();
            return new Response(json_encode(array(
                                "data" => array($id => $stateJson))), 200,
                            array('Content-Type' => 'application/json'));
        } else
        {
            return new Response(json_encode(array(
                                "error" => 'Error: wmc "' . $id . '" not found')), 200,
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
        $entities = $this->container
                ->get('doctrine')
                ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
                ->findAll();
        $responseBody = $this->container
                ->get('templating')
                ->render('MapbenderWmcBundle:Wmc:index.html.twig',
                         array("entities" => $entities)
        );

        $response->setContent($responseBody);
        return $response;
    }
//
//    protected function save($id = null)
//    {
//        $response = new Response();
//        $request = $this->container->get('request');
//
//        if($id)
//        {
//            $wmc = $this->container->get('doctrine')
//                    ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
//                    ->find($id);
//        } else
//        {
//            $wmc = Wmc::create();
//        }
//        $form = $this->container->get("form.factory")->create(new WmcType(),
//                                                              $wmc);
//        if($request->getMethod() === 'POST')
//        {
//            $form->bindRequest($request);
//            if($form->isValid())
//            { //TODO: Is file an image (jpg/png/gif?)
//                $em = $this->container->get('doctrine')->getEntityManager();
//                $em->getConnection()->beginTransaction();
//                $em->persist($wmc);
//                $em->flush();
//                if($wmc->getScreenshotPath() === null)
//                {
//                    if($wmc->getScreenshot() !== null)
//                    {
//                        $upload_directory = $this->createWmcDirs();
//                        if($upload_directory !== null)
//                        {
//                            $dirs = $this->container->getParameter("directories");
//                            $filename = sprintf('screenshot-%d.%s',
//                                                $wmc->getId(),
//                                                $wmc->getScreenshot()->guessExtension());
//                            $wmc->getScreenshot()->move($upload_directory,
//                                                        $filename);
//                            $wmc->setScreenshotPath($filename);
//                            $format = $wmc->getScreenshot()->getClientMimeType();
//                            $url_base = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
//                            $serverurl = $url_base . "/" . $dirs["wmc"];
//                            $logourl = $serverurl . "/" . $this->application->getSlug() . "/" . $filename;
//                            $logoUrl = LegendUrl::create(null, null,
//                                                         OnlineResource::create($format,
//                                                                                $logourl));
//                            $state = $wmc->getState();
//                            $state->setServerurl($serverurl);
//                            $state->setSlug($this->application->getSlug());
//                            $wmc->setLogourl($logoUrl);
//                        }
//                    } else
//                    {
//                        $wmc->setScreenshotPath(null);
//                    }
//                    $em->persist($wmc);
//                    $em->flush();
//                }
//
////                $patern = array('/"?minScale"?:\s?null\s?,?/', '/"?maxScale"?:\s?null\s?,?/');
////                $rplmt = array("", "");
////                $services = preg_replace($patern, $rplmt,
////                                         $wmc->getServices());
////                $wmc->setServices($services);
////
////                $em->persist($wmc);
////                $em->flush();
////
////                $path = $this->getParameter("themenkartenwmc_directory");
////                $path .= "/themenkarte_" . $wmc->getId() . "wmc.xml";
////                file_put_contents($path, $wmc->getWmc() ? : "");
//                $em->getConnection()->commit();
//                $response->setContent($wmc->getId());
//            } else
//            {
//                $response->setContent('error');
//            }
//        }
//        return $response;
//    }

    private function delete($id)
    {
        $response = new Response();
        $wmc = $this->container->get('doctrine')
                ->getRepository('Mapbender\WmcBundle\Entity\Wmc')
                ->find($id);
        if($wmc !== null)
        {
            $response->setContent($wmc->getId());
            $em = $this->container->get('doctrine')->getEntityManager();
            $em->remove($wmc);
            $em->flush();
        } else
        {
            $response->setContent('error');
        }
        return $response;
    }
//
//    public function generateMetadata($themenkarte)
//    {
//        return $this->get('templating')->render('BkgGeoportalBundle:Element:themenkarteneditor_wmcmetadata.html.twig',
//                                                array("themenkarte" => $themenkarte)
//        );
//    }
//
//    protected function createWmcDirs()
//    {
//        $basedir = $this->container->get('kernel')->getRootDir() . '/../web/';
//        $dirs = $this->container->getParameter("directories");
//        $dir = $basedir . $dirs["wmc"] . "/" . $this->application->getSlug();
//        if(!is_dir($dir))
//        {
//            $a = mkdir($dir);
//            if($a)
//            {
//                return $dir;
//            } else
//            {
//                return null;
//            }
//        } else
//        {
//            return $dir;
//        }
//    }

}

