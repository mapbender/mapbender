<?php

namespace Mapbender\WmsBundle\Controller;

use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ManagerRoute("/repository/wms")
 *
 * @author Christian Wygoda
 */
class RepositoryController extends Controller
{
    /**
     * Edits, saves the WmsInstance
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
        /** @var WmsInstance|null $instance */
        $instance = $em->getRepository("MapbenderCoreBundle:SourceInstance")->find($instanceId);

        $form = $this->createForm('wmsinstanceinstancelayers', $instance);
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
        return $this->render('@MapbenderWms/Repository/instance.html.twig', array(
            "form" => $form->createView(),
            "slug" => $slug,
            "instance" => $instance,
        ));
    }
}
