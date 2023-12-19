<?php


namespace Mapbender\ManagerBundle\Controller;


use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Entity\Layerset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LayersetController extends ApplicationControllerBase
{
    /**
     * Handle create + modify
     *
     * @ManagerRoute("/application/{slug}/layerset/new", methods={"GET", "POST"}, name="mapbender_manager_layerset_new")
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/edit", methods={"GET", "POST"}, name="mapbender_manager_layerset_edit")
     * @param Request $request
     * @param string $slug
     * @param string|null $layersetId
     * @return Response
     */
    public function editAction(Request $request, $slug, $layersetId = null)
    {
        if ($layersetId) {
            $layerset = $this->requireLayerset($layersetId);
            $application = $layerset->getApplication();
        } else {
            $application = $this->requireDbApplication($slug);
            $layerset = new Layerset();
            $layerset->setApplication($application);
        }
        $this->denyAccessUnlessGranted('EDIT', $application);

        $form = $this->createForm('Mapbender\CoreBundle\Form\Type\LayersetType', $layerset);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em = $this->getEntityManager();
                $application->setUpdated(new \DateTime('now'));
                $em->persist($application);
                $em->persist($layerset);
                $em->flush();
                $this->addFlash('success', 'mb.layerset.create.success');
            } else {
                foreach ($form->getErrors(false, true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
            return $this->redirectToRoute('mapbender_manager_application_edit', array(
                'slug' => $slug,
                '_fragment' => 'tabLayers',
            ));
        }

        return $this->render('@MapbenderManager/Layerset/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/delete", methods={"GET", "POST", "DELETE"})
     * @param Request $request
     * @param string $slug
     * @param string $layersetId
     * @return Response
     */
    public function deleteAction(Request $request, $slug, $layersetId)
    {
        $layerset = $this->requireLayerset($layersetId);
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);
        if ($request->getMethod() === Request::METHOD_GET) {
            // Render confirmation dialog content
            return $this->render('@MapbenderManager/Application/deleteLayerset.html.twig', array(
                'layerset' => $layerset,
            ));
        } else {
            if (!$this->isCsrfTokenValid('layerset_delete', $request->request->get('token'))) {
                throw new BadRequestHttpException();
            }

            $em = $this->getEntityManager();
            $em->remove($layerset);
            $application->setUpdated(new \DateTime('now'));
            $em->persist($application);
            $em->flush();
            $this->addFlash('success', 'mb.layerset.remove.success');
            /** @todo: perform redirect server side, not client side */
            return new Response();
        }
    }

    /**
     * Setter action for "selected" flag (single value, immediate by ajax, no form)
     *
     * @ManagerRoute("/layerset/{layerset}/toggleselected", methods={"POST"})
     * @param Request $request
     * @param Layerset $layerset
     * @return Response
     */
    public function setselectedAction(Request $request, Layerset $layerset)
    {
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);

        if (!$this->isCsrfTokenValid('layerset', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $em = $this->getEntityManager();
        $layerset->setSelected($request->request->getBoolean('enabled'));
        $application->setUpdated(new \DateTime('now'));
        $em->persist($layerset);
        $em->persist($application);
        $em->flush();
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
