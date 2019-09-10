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
        $em = $this->getDoctrine()->getManager();
        /** @var WmtsInstance|null $instance */
        $instance = $em->getRepository("MapbenderCoreBundle:SourceInstance")->find($instanceId);

        $form = $this->createForm(new WmtsInstanceInstanceLayersType(), $instance);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($instance);
            $layerSet = $instance->getLayerset();
            if ($layerSet) {
                $application = $layerSet->getApplication();
                if ($application) {
                    $application->setUpdated(new \DateTime('now'));
                    $em->persist($application);
                }
            }
            $em->flush();

            $this->addFlash('success', 'Your instance has been updated.');
            return $this->redirectToRoute('mapbender_manager_application_edit', array(
                "slug" => $slug,
            ));
        }
        return $this->render('@MapbenderWmts/Repository/instance.html.twig', array(
            "form" => $form->createView(),
            "slug" => $slug,
            "instance" => $instance,
        ));
    }
}
