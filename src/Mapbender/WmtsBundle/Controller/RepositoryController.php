<?php

namespace Mapbender\WmtsBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\ManagerBundle\Form\Type\HttpSourceOriginType;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Mapbender\WmtsBundle\Form\Type\WmtsInstanceInstanceLayersType;
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
        $form = $this->createForm(new HttpSourceOriginType(), $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Mapbender\WmtsBundle\Component\Wmts\Loader $loader */
            $loader = $this->get('mapbender.importer.source.wmts.service');
            try {
                $loaderResponse = $loader->evaluateServer($formModel, false);
                $source = $loaderResponse->getSource();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute("mapbender_manager_repository_new");
            }
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()->getManager();
            $wmtsWithSameTitle = $em->getRepository("MapbenderWmtsBundle:WmtsSource")
                ->findBy(array('title' => $source->getTitle()));

            if (count($wmtsWithSameTitle) > 0) {
                $source->setAlias(count($wmtsWithSameTitle));
            }

            $em->persist($source);
            $em->flush();

            /** @var MutableAclProviderInterface $aclProvider */
            $aclProvider    = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($source);
            $acl            = $aclProvider->createAcl($objectIdentity);

            $securityIdentity = UserSecurityIdentity::fromAccount($this->getUser());

            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);

            $this->addFlash('success', "Your WMTS has been created");
            return $this->redirectToRoute("mapbender_manager_repository_view", array(
                "sourceId" => $source->getId(),
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
