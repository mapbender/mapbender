<?php

namespace Mapbender\WmtsBundle\Controller;

use Doctrine\ORM\EntityManager;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmtsBundle\Component\Exception\NoWmtsDocument;
use Mapbender\WmtsBundle\Component\TmsCapabilitiesParser100;
use Mapbender\WmtsBundle\Component\WmtsCapabilitiesParser;
use Mapbender\WmtsBundle\Component\WmtsInstanceEntityHandler;
use Mapbender\WmtsBundle\Component\WmtsSourceEntityHandler;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Mapbender\WmtsBundle\Form\Type\WmtsInstanceInstanceLayersType;
use Mapbender\WmtsBundle\Form\Type\WmtsSourceSimpleType;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * @ManagerRoute("/repository/wmts")
 *
 * @author Paul Schmidt
 */
class RepositoryController extends Controller
{
    /**
     * @ManagerRoute("/new", methods={"GET"})
     */
    public function newAction()
    {
        $form = $this->createForm(new WmtsSourceSimpleType(), new WmtsSource(Source::TYPE_WMTS));
        return $this->render('@MapbenderWmts/Repository/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @ManagerRoute("/start", methods={"GET"})
     */
    public function startAction()
    {
        $form = $this->createForm(new WmtsSourceSimpleType(), new WmtsSource(Source::TYPE_WMTS));
        return $this->render('@MapbenderWmts/Repository/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @ManagerRoute("{wmts}", methods={"GET"})
     */
    public function viewAction(WmtsSource $wmts)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$this->isGranted('VIEW', $oid)) {
            $this->denyAccessUnlessGranted('VIEW', $wmts);
        }
        return $this->render('@MapbenderWmts/Repository/view.html.twig', array(
            "wmts" => $wmts,
        ));
    }

    /**
     * @ManagerRoute("/create", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('CREATE', $oid);
        $wmtssource_req = new WmtsSource(Source::TYPE_WMTS);
        $form = $this->createForm(new WmtsSourceSimpleType(), $wmtssource_req);
        $form->submit($request);

        $onlyvalid = false;
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
                ->findBy(array('title' => $wmtssource->getTitle()));

            if (count($wmtsWithSameTitle) > 0) {
                $wmtssource->setAlias(count($wmtsWithSameTitle));
            }

            $wmtssource->setOriginUrl($wmtssource_req->getOriginUrl());
//            $rootlayer = $wmtssource->getLayers()->get(0);
//            $this->getDoctrine()->getManager()->persist($rootlayer);
//            $this->saveLayer($this->getDoctrine()->getManager(), $rootlayer);

            WmtsSourceEntityHandler::createHandler($this->container, $wmtssource)->save();
//            $this->getDoctrine()->getManager()->persist($wmtssource);
            $this->getDoctrine()->getManager()->flush();

            /** @var MutableAclProviderInterface $aclProvider */
            $aclProvider    = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($wmtssource);
            $acl            = $aclProvider->createAcl($objectIdentity);

            $securityIdentity = UserSecurityIdentity::fromAccount($this->getUser());

            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);

            $this->get('session')->getFlashBag()->set('success', "Your WMTS has been created");
            return $this->redirect($this->generateUrl(
                "mapbender_manager_repository_view",
                array("sourceId" => $wmtssource->getId()),
                true
            ));
        }

        return $this->render('@MapbenderWmts/Repository/new.html.twig', array(
            'form' => $form->createView(),
            'form_name' => $form->getName(),
        ));
    }

    /**
     * Removes a WmtsSource
     *
     * @ManagerRoute("/{sourceId}/delete", methods={"GET"})
     * @param string $sourceId
     * @return Response
     */
    public function deleteAction($sourceId)
    {
        $wmtssource    = $this->getDoctrine()
            ->getRepository("MapbenderWmtsBundle:WmtsSource")
            ->find($sourceId);
        $wmtsinstances = $this->getDoctrine()
            ->getRepository("MapbenderWmtsBundle:WmtsInstance")
            ->findBy(array('source' => $sourceId));
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
     * @ManagerRoute("/{slug}/instance/{instanceId}/delete", methods={"GET"})
     * @param string $slug
     * @param string $instanceId
     * @return Response
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
     * @param Request $request
     * @param string $slug
     * @param string $instanceId
     * @return Response
     */
    public function instanceAction(Request $request, $slug, $instanceId)
    {
        /** @var WmtsInstance|null $wmtsinstance */
        $wmtsinstance = $this->getDoctrine()
            ->getRepository("MapbenderWmtsBundle:WmtsInstance")
            ->find($instanceId);

        $form = $this->createForm(new WmtsInstanceInstanceLayersType(), $wmtsinstance);
        if ($request->getMethod() == 'POST') { //save
            $form->submit($request);
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
                $wmtsinsthandler = new WmtsInstanceEntityHandler($this->container, $wmtsinstance);
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
            }
        }
        return $this->render('@MapbenderWmts/Repository/instance.html.twig', array(
            "form" => $form->createView(),
            "slug" => $slug,
            "instance" => $wmtsinstance,
        ));
    }

    /**
     * Changes the priority of WmtsInstanceLayers
     *
     * @ManagerRoute("/{slug}/instance/{instanceId}/priority/{instLayerId}")
     * @param Request $request
     * @param string $slug
     * @param string $instanceId
     * @param string $instLayerId
     * @return Response
     */
    public function instanceLayerPriorityAction(Request $request, $slug, $instanceId, $instLayerId)
    {
        $number = $request->get("number");
        /** @var WmtsInstanceLayer|null $instLay */
        $instLay = $this->getDoctrine()
            ->getRepository('MapbenderWmtsBundle:WmtsInstanceLayer')
            ->findOneBy(array('id' => $instLayerId));

        if (!$instLay) {
            return new JsonResponse(array(
                'error' => 'The wmts instance layer with id "' . $instanceId . '" does not exist.',
                'result' => '',
            ));
        }
        if (intval($number) === $instLay->getPriority()) {
            return new JsonResponse(array(
                'error' => '',
                'result' => 'ok',
            ));
        }
        /** @var EntityManager $em */
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
        $wmtsinsthandler = new WmtsInstanceEntityHandler($this->container, $wmtsinstance);
        $wmtsinsthandler->save();
        $em->flush();
        $em->getConnection()->commit();
        return new JsonResponse(array(
            // why?
            'error' => '',
            'result' => 'ok',
        ));
    }

    /**
     * Sets enabled/disabled for the WmtsInstance
     *
     * @ManagerRoute("/instance/{slug}/enabled/{instanceId}", methods={"POST"})
     * @param Request $request
     * @param string $slug
     * @param string $instanceId
     * @return Response
     */
    public function instanceEnabledAction(Request $request, $slug, $instanceId)
    {
        $enabled = $request->get("enabled");
        /** @var WmtsInstance|null $wmtsinstance */
        $wmtsinstance = $this->getDoctrine()
            ->getRepository("MapbenderWmtsBundle:WmtsInstance")
            ->find($instanceId);
        if (!$wmtsinstance) {
            return new JsonResponse(array(
                // why?
                'error' => 'The wmts instance with the id "' . $instanceId . '" does not exist.',
            ));
        } else {
            $enabled_before = $wmtsinstance->getEnabled();
            $enabled        = $enabled === "true" ? true : false;
            $wmtsinstance->setEnabled($enabled);
            $em             = $this->getDoctrine()->getManager();
            $em->persist($wmtsinstance);
            $em->flush();
            return new JsonResponse(array(
                'success' => array(
                    "id" => $wmtsinstance->getId(),
                    "type" => "instance",
                    "enabled" => array(
                        'before' => $enabled_before,
                        'after' => $enabled,
                    ),
                ),
            ));
        }
    }
}
