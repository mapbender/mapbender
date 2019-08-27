<?php

namespace Mapbender\WmtsBundle\Controller;

use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Form\Type\WmtsInstanceInstanceLayersType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ManagerRoute("/repository/wmts")
 *
 * @author Paul Schmidt
 */
class RepositoryController extends Controller
{
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
