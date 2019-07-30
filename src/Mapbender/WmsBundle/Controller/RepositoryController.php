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
        $repositoryName = "MapbenderWmsBundle:WmsInstance";
        /** @var WmsInstance|null $wmsinstance */
        $wmsinstance = $this->loadEntityByPk($repositoryName, $instanceId);

        if ($request->getMethod() == 'POST') { //save
            $form = $this->createForm('wmsinstanceinstancelayers', $wmsinstance);
            $form->submit($request);
            if ($form->isValid()) { //save
                $em = $this->getDoctrine()->getManager();
                $em->getConnection()->beginTransaction();
                $em->persist($wmsinstance);
                $layerSet = $wmsinstance->getLayerset();
                if ($layerSet) {
                    $application = $layerSet->getApplication();
                    if ($application) {
                        $application->setUpdated(new \DateTime('now'));
                        $em->persist($application);
                    }
                }
                $em->flush();
                $em->getConnection()->commit();

                $this->addFlash('success', 'Your Wms Instance has been changed.');
                return $this->redirectToRoute('mapbender_manager_application_edit', array(
                    "slug" => $slug,
                ));
            } else { // edit
                $this->addFlash('warning', 'Your Wms Instance is not valid.');
                return $this->render('@MapbenderWms/Repository/instance.html.twig', array(
                    "form" => $form->createView(),
                    "slug" => $slug,
                    "instance" => $wmsinstance,
                ));
            }
        } else { // edit
            /* bug fix start @TODO remove after migration's introduction */
            foreach ($wmsinstance->getLayers() as $layer) {
                if ($layer->getSublayer()->count() === 0) {
                    $layer->setToggle(null);
                    $layer->setAllowtoggle(null);
                }
            }
            /* bug fix end */
            $form = $this->createForm('wmsinstanceinstancelayers', $wmsinstance);
            return $this->render('@MapbenderWms/Repository/instance.html.twig', array(
                "form" => $form->createView(),
                "slug" => $slug,
                "instance" => $wmsinstance,
            ));
        }
    }

    /**
     * @param string $repositoryName
     * @param mixed $id
     * @return object|null
     */
    protected function loadEntityByPk($repositoryName, $id)
    {
        return $this->getDoctrine()->getRepository($repositoryName)->find($id);
    }
}
