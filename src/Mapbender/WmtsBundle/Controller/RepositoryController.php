<?php

namespace Mapbender\WmtsBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\WmtsBundle\Component\Exception\NoWmtsDocument;
use Mapbender\WmtsBundle\Component\TmsCapabilitiesParser100;
use Mapbender\WmtsBundle\Component\WmtsCapabilitiesParser;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Mapbender\WmtsBundle\Form\Type\WmtsInstanceInstanceLayersType;
use Mapbender\WmtsBundle\Form\Type\WmtsSourceSimpleType;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
     * @ManagerRoute("/start", methods={"GET"})
     */
    public function startAction()
    {
        $form = $this->createForm(new WmtsSourceSimpleType(), new WmtsSource());
        return $this->render('@MapbenderWmts/Repository/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @ManagerRoute("{wmts}", methods={"GET"})
     * @param WmtsSource $wmts
     * @return Response
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

        $formModel = new HttpOriginModel();
        $form = $this->createForm(new WmtsSourceSimpleType(), $formModel);
        $form->submit($request);

        if ($form->isValid()) {
            $purl = parse_url($formModel->getOriginUrl());
            if (!isset($purl['scheme']) || !isset($purl['host'])) {
                $this->get("logger")->debug("The url is not valid.");
                $this->addFlash('error', "The url is not valid.");
                return $this->redirectToRoute("mapbender_manager_repository_new");
            }
            $proxy_config = $this->container->getParameter("owsproxy.proxy");
            $proxy_query  = ProxyQuery::createFromUrl(
                $formModel->getOriginUrl(),
                $formModel->getUsername(),
                $formModel->getPassword()
            );
            $proxy = new CommonProxy($proxy_config, $proxy_query);

            $wmtssource = null;
            try {
                $browserResponse = $proxy->handle();
                $content         = $browserResponse->getContent();
                try {
                    $doc = WmtsCapabilitiesParser::createDocument($content);
                    $parser = WmtsCapabilitiesParser::getParser($doc);
                } catch (NoWmtsDocument $e) {
                    $doc = TmsCapabilitiesParser100::createDocument($content);
                    $parser = TmsCapabilitiesParser100::getParser($proxy_config, $doc);
                }
                $wmtssource = $parser->parse();
            } catch (\Exception $e) {
                $this->get("logger")->err($e->getMessage());
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute("mapbender_manager_repository_new");
            }

            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()->getManager();
            $wmtsWithSameTitle = $em->getRepository("MapbenderWmtsBundle:WmtsSource")
                ->findBy(array('title' => $wmtssource->getTitle()));

            if (count($wmtsWithSameTitle) > 0) {
                $wmtssource->setAlias(count($wmtsWithSameTitle));
            }

            $wmtssource->setOriginUrl($formModel->getOriginUrl());
            $em->persist($wmtssource);
            $em->flush();

            /** @var MutableAclProviderInterface $aclProvider */
            $aclProvider    = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($wmtssource);
            $acl            = $aclProvider->createAcl($objectIdentity);

            $securityIdentity = UserSecurityIdentity::fromAccount($this->getUser());

            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);

            $this->addFlash('success', "Your WMTS has been created");
            return $this->redirectToRoute("mapbender_manager_repository_view", array(
                "sourceId" => $wmtssource->getId(),
            ));
        }
        return $this->forward('MapbenderManagerBundle:Repository:new');
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
                $em->persist($wmtsinstance);
                $em->flush();
                $em->getConnection()->commit();
                $this->addFlash('success', 'Your Wmts Instance has been changed.');
                return $this->redirectToRoute('mapbender_manager_application_edit', array(
                    "slug" => $slug,
                ));
            }
        }
        return $this->render('@MapbenderWmts/Repository/instance.html.twig', array(
            "form" => $form->createView(),
            "slug" => $slug,
            "instance" => $wmtsinstance,
        ));
    }
}
