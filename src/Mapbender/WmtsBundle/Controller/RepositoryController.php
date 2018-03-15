<?php

namespace Mapbender\WmtsBundle\Controller;

use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\CoreBundle\Component\XmlValidator;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmtsBundle\Component\Exception\NoWmtsDocument;
use Mapbender\WmtsBundle\Component\Exception\WmtsException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\WmtsBundle\Component\TmsCapabilitiesParser100;
use Mapbender\WmtsBundle\Component\WmtsCapabilitiesParser;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Mapbender\WmtsBundle\Form\Type\WmtsInstanceInstanceLayersType;
use Mapbender\WmtsBundle\Form\Type\WmtsSourceSimpleType;
use Mapbender\WmtsBundle\Form\Type\WmtsSourceType;
use Mapbender\WmtsBundle\Form\Type\WmtsInstanceType;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @ManagerRoute("/repository/wmts")
 *
 * @author Paul Schmidt
 */
class RepositoryController extends Controller
{
    public static $WMTS_DIR = "xml/wmts";

    /**
     * @ManagerRoute("/new")
     * @Method({ "GET" })
     * @Template
     */
    public function newAction()
    {
        $form = $this->get("form.factory")->create(new WmtsSourceSimpleType(), new WmtsSource(Source::TYPE_WMTS));
        return array(
            "form" => $form->createView()
        );
    }

    /**
     * @ManagerRoute("/start")
     * @Method({ "GET" })
     * @Template("MapbenderWmtsBundle:Repository:form.html.twig")
     */
    public function startAction()
    {
        $form = $this->get("form.factory")->create(new WmtsSourceSimpleType(), new WmtsSource(Source::TYPE_WMTS));
        return array(
            "form" => $form->createView()
        );
    }

    /**
     * @ManagerRoute("{wmts}")
     * @Method({ "GET"})
     * @Template
     */
    public function viewAction(WmtsSource $wmts)
    {
        $securityContext = $this->get('security.context');
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('VIEW', $wmts)) {
            throw new AccessDeniedException();
        }
        return array("wmts" => $wmts);
    }

    /**
     * @ManagerRoute("/create")
     * @Method({ "POST" })
     * @Template("MapbenderWmtsBundle:Repository:new.html.twig")
     */
    public function createAction()
    {
        $request        = $this->get('request');
        $wmtssource_req = new WmtsSource(Source::TYPE_WMTS);

        $securityContext = $this->get('security.context');
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (false === $securityContext->isGranted('CREATE', $oid)) {
            throw new AccessDeniedException();
        }

        $form      = $this->get("form.factory")->create(new WmtsSourceSimpleType(), $wmtssource_req);
        $form->bind($request);
        $onlyvalid = $form->get('onlyvalid')->getData();
        if ($form->isValid()) {
            $purl = parse_url($wmtssource_req->getOriginUrl());
            if (!isset($purl['scheme']) || !isset($purl['host'])) {
                $this->get("logger")->debug("The url is not valid.");
                $this->get('session')->getFlashBag()->set('error', "The url is not valid.");
                return $this->redirect($this->generateUrl("mapbender_manager_repository_new", array(), true));
            }
            $proxy_config = $this->container->getParameter("owsproxy.proxy");
            $proxy_query  = ProxyQuery::createFromUrl(
                trim($wmtssource_req->getOriginUrl()),
                $wmtssource_req->getUsername(),
                $wmtssource_req->getPassword()
            );
            $wmtssource_req->setOriginUrl($proxy_query->getGetUrl());
            $proxy = new CommonProxy($proxy_config, $proxy_query);

            $wmtssource = null;
            try {
                $browserResponse = $proxy->handle();
                $content         = $browserResponse->getContent();
                try {
                    $doc = WmtsCapabilitiesParser::createDocument($content);
                    if ($onlyvalid === true) {
                        // $validator = new XmlValidator($this->container, $proxy_config, "xmlschemas/");
                        // $doc = $validator->validate($doc);
                        $wmtsParser = WmtsCapabilitiesParser::getParser($doc);
                        $wmtssource = $wmtsParser->parse();
                        $wmtssource->setValid(true);
                    } else {
                        try {
                            // $validator = new XmlValidator($this->container, $proxy_config, "xmlschemas/");
                            // $doc = $validator->validate($doc);
                            $wmtsParser = WmtsCapabilitiesParser::getParser($doc);
                            $wmtssource = $wmtsParser->parse();
                            $wmtssource->setValid(true);
                        } catch (\Exception $e) {
                            $this->get("logger")->warn($e->getMessage());
                            $this->get('session')->getFlashBag()->set('warning', $e->getMessage());
                            $wmtsParser = WmtsCapabilitiesParser::getParser($doc);
                            $wmtssource = $wmtsParser->parse();
                            $wmtssource->setValid(false);
                        }
                    }
                } catch (NoWmtsDocument $e) {
                    $doc = TmsCapabilitiesParser100::createDocument($content);
                    try {
                        $tmsParser = TmsCapabilitiesParser100::getParser($proxy_config, $doc);
                        $wmtssource = $tmsParser->parse();
                        $wmtssource->setValid(false);
                    } catch (\Exception $e) {
                        $this->get("logger")->warn($e->getMessage());
                        $this->get('session')->getFlashBag()->set('warning', $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $this->get("logger")->err($e->getMessage());
                $this->get('session')->getFlashBag()->set('error', $e->getMessage());
                return $this->redirect($this->generateUrl("mapbender_manager_repository_new", array(), true));
            }

            if (!$wmtssource) {
                $this->get("logger")->err('Could not parse data for url "'
                    . $wmtssource_req->getOriginUrl() . '"');
                $this->get('session')->getFlashBag()->set('error', 'Could not parse data for url "'
                    . $wmtssource_req->getOriginUrl() . '"');
                return $this->redirect($this->generateUrl("mapbender_manager_repository_new", array(), true));
            }
            $wmtsWithSameTitle = $this->getDoctrine()
                ->getManager()
                ->getRepository("MapbenderWmtsBundle:WmtsSource")
                ->findByTitle($wmtssource->getTitle());

            if (count($wmtsWithSameTitle) > 0) {
                $wmtssource->setAlias(count($wmtsWithSameTitle));
            }

            $wmtssource->setOriginUrl($wmtssource_req->getOriginUrl());
//            $rootlayer = $wmtssource->getLayers()->get(0);
//            $this->getDoctrine()->getManager()->persist($rootlayer);
//            $this->saveLayer($this->getDoctrine()->getManager(), $rootlayer);

            EntityHandler::createHandler($this->container, $wmtssource)->save();
//            $this->getDoctrine()->getManager()->persist($wmtssource);
            $this->getDoctrine()->getManager()->flush();

            // ACL
            $aclProvider    = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($wmtssource);
            $acl            = $aclProvider->createAcl($objectIdentity);

            $securityContext  = $this->get('security.context');
            $user             = $securityContext->getToken()->getUser();
            $securityIdentity = UserSecurityIdentity::fromAccount($user);

            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);

            $this->get('session')->getFlashBag()->set('success', "Your WMTS has been created");
            return $this->redirect($this->generateUrl(
                "mapbender_manager_repository_view",
                array("sourceId" => $wmtssource->getId()),
                true
            ));
        }

        return array(
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    private function saveLayer($em, $layer)
    {
        foreach ($layer->getSublayer() as $sublayer) {
            $em->persist($sublayer);
            $this->saveLayer($em, $sublayer);
        }
    }

    /**
     * Removes a WmtsSource
     *
     * @ManagerRoute("/{sourceId}/delete")
     * @Method({"GET"})
     */
    public function deleteAction($sourceId)
    {
        $wmtssource    = $this->getDoctrine()
            ->getRepository("MapbenderWmtsBundle:WmtsSource")
            ->find($sourceId);
        $wmtsinstances = $this->getDoctrine()
            ->getRepository("MapbenderWmtsBundle:WmtsInstance")
            ->findBySource($sourceId);
        $em            = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();

        $aclProvider = $this->get('security.acl.provider');
        $oid         = ObjectIdentity::fromDomainObject($wmtssource);
        $aclProvider->deleteAcl($oid);

        foreach ($wmtsinstances as $wmtsinstance) {
            EntityHandler::createHandler($this->container, $wmtsinstance)->remove();
        }
        EntityHandler::createHandler($this->container, $wmtssource)->remove();
        $em->flush();
        $em->getConnection()->commit();
        $this->get('session')->getFlashBag()->set('success', "Your WMTS has been deleted");
        return $this->redirect($this->generateUrl("mapbender_manager_repository_index"));
    }

    /**
     * Removes a WmtsInstance
     *
     * @ManagerRoute("/{slug}/instance/{instanceId}/delete")
     * @Method({"GET"})
     */
    public function deleteInstanceAction($slug, $instanceId)
    {
        $instance = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);
        $em       = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        EntityHandler::createHandler($this->container, $instance)->remove();
        $em->flush();
        $em->getConnection()->commit();
        $this->get('session')->getFlashBag()->set('success', 'Your source instance has been deleted.');
        return new Response();
    }

    /**
     * Edits, saves the WmtsInstance
     *
     * @ManagerRoute("/instance/{slug}/{instanceId}")
     * @Template("MapbenderWmtsBundle:Repository:instance.html.twig")
     */
    public function instanceAction($slug, $instanceId)
    {
        $wmtsinstance = $this->getDoctrine()
            ->getRepository("MapbenderWmtsBundle:WmtsInstance")
            ->find($instanceId);

        if ($this->getRequest()->getMethod() == 'POST') { //save
            $form = $this->createForm(new WmtsInstanceInstanceLayersType(), $wmtsinstance);
            $form->bind($this->get('request'));
            if ($form->isValid()) { //save
                $em = $this->getDoctrine()->getManager();
                $em->getConnection()->beginTransaction();
                foreach ($wmtsinstance->getLayers() as $layer) {
                    $em->persist($layer);
                    $em->flush();
                    $em->refresh($layer);
                }
                $em->persist($wmtsinstance);
                $em->flush();
                $wmtsinstance    = $this->getDoctrine()
                    ->getRepository("MapbenderWmtsBundle:WmtsInstance")
                    ->find($wmtsinstance->getId());
                $wmtsinsthandler = EntityHandler::createHandler($this->container, $wmtsinstance);
                $wmtsinsthandler->generateConfiguration();
                $wmtsinsthandler->save();
                $em->flush();
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->set('success', 'Your Wmts Instance has been changed.');
                return $this->redirect(
                    $this->generateUrl(
                        'mapbender_manager_application_edit',
                        array("slug" => $slug)
                    ) . '#layersets'
                );
            } else { // edit
                return array(
                    "form" => $form->createView(),
                    "slug" => $slug,
                    "instance" => $wmtsinstance);
            }
        } else { // edit
            $form = $this->createForm(new WmtsInstanceInstanceLayersType(), $wmtsinstance);
            $fv   = $form->createView();
            return array(
                "form" => $form->createView(),
                "slug" => $slug,
                "instance" => $wmtsinstance);
        }
    }

    /**
     * Changes the priority of WmtsInstanceLayers
     *
     * @ManagerRoute("/{slug}/instance/{instanceId}/priority/{instLayerId}")
     */
    public function instanceLayerPriorityAction($slug, $instanceId, $instLayerId)
    {
        $number  = $this->get("request")->get("number");
        $instLay = $this->getDoctrine()
            ->getRepository('MapbenderWmtsBundle:WmtsInstanceLayer')
            ->findOneById($instLayerId);

        if (!$instLay) {
            return new Response(json_encode(array(
                    'error' => 'The wmts instance layer with'
                    . ' the id "' . $instanceId . '" does not exist.',
                    'result' => '')), 200, array('Content-Type' => 'application/json'));
        }
        if (intval($number) === $instLay->getPriority()) {
            return new Response(json_encode(array(
                    'error' => '',
                    'result' => 'ok')), 200, array('Content-Type' => 'application/json'));
        }
        $em       = $this->getDoctrine()->getManager();
//        $instLay->setPriority($number);
        $em->persist($instLay);
        $em->flush();
        $query    = $em->createQuery(
            "SELECT il FROM MapbenderWmtsBundle:WmtsInstanceLayer il"
            . " WHERE il.wmtsinstance=:wmtsi ORDER BY il.priority ASC"
        );
        $query->setParameters(array("wmtsi" => $instanceId));
        $instList = $query->getResult();

        $num = 0;
        foreach ($instList as $inst) {
            if ($num === intval($instLay->getPriority())) {
                if ($instLay->getId() === $inst->getId()) {
                    $num++;
                } else {
                    $num++;
                    $inst->setPriority($num);
                    $num++;
                }
            } else {
                if ($instLay->getId() !== $inst->getId()) {
                    $inst->setPriority($num);
                    $num++;
                }
            }
        }
        $em->getConnection()->beginTransaction();
        foreach ($instList as $inst) {
            $em->persist($inst);
        }
        $em->flush();
        $wmtsinstance    = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);
        $wmtsinsthandler = EntityHandler::createHandler($this->container, $wmtsinstance);
        $wmtsinsthandler->generateConfiguration();
        $wmtsinsthandler->save();
        $em->flush();
        $em->getConnection()->commit();
        return new Response(json_encode(array(
                'error' => '',
                'result' => 'ok')), 200, array(
            'Content-Type' => 'application/json'));
    }

    /**
     * Sets enabled/disabled for the WmtsInstance
     *
     * @ManagerRoute("/instance/{slug}/enabled/{instanceId}")
     * @Method({ "POST" })
     */
    public function instanceEnabledAction($slug, $instanceId)
    {
        $enabled      = $this->get("request")->get("enabled");
        $wmtsinstance = $this->getDoctrine()
            ->getRepository("MapbenderWmtsBundle:WmtsInstance")
            ->find($instanceId);
        if (!$wmtsinstance) {
            return new Response(
                json_encode(array('error' => 'The wmts instance with the id "' . $instanceId . '" does not exist.')),
                200,
                array('Content-Type' => 'application/json')
            );
        } else {
            $enabled_before = $wmtsinstance->getEnabled();
            $enabled        = $enabled === "true" ? true : false;
            $wmtsinstance->setEnabled($enabled);
            $em             = $this->getDoctrine()->getManager();
            $em->persist($wmtsinstance);
            $em->flush();
            return new Response(json_encode(array(
                    'success' => array(
                        "id" => $wmtsinstance->getId(),
                        "type" => "instance",
                        "enabled" => array(
                            'before' => $enabled_before,
                            'after' => $enabled)))), 200, array('Content-Type' => 'application/json'));
        }
    }

    /**
     * Get Metadata for a wmts service
     *
     * @ManagerRoute("/instance/metadata")
     * @Method({ "POST" })
     */
    public function metadataAction()
    {
        $sourceId        = $this->container->get('request')->get("sourceId", null);
        $instance        = $this->container->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($sourceId);
        $securityContext = $this->get('security.context');
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$securityContext->isGranted('VIEW', $oid) &&
            !$securityContext->isGranted('VIEW', $instance->getSource())) {
            throw new AccessDeniedException();
        }
        $layerName = $this->container->get('request')->get("layerName", null);
        $metadata  = $instance->getMetadata();
        $metadata->setContenttype(SourceMetadata::$CONTENTTYPE_ELEMENT);
        $metadata->setContainer(SourceMetadata::$CONTAINER_ACCORDION);
        $content   = $metadata->render($this->container->get('templating'), $layerName);
        $response  = new Response();
        $response->setContent($content);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }
}
