<?php


namespace Mapbender\ManagerBundle\Controller;


use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\CoreBundle\Entity\Layerset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LayersetController extends ApplicationControllerBase
{
    /**
     * Handle create + modify
     *
     * @param Request $request
     * @param string $slug
     * @param string|null $layersetId
     * @return Response
     */
    #[ManagerRoute('/application/{slug}/layerset/new', methods: ['GET', 'POST'], name: 'mapbender_manager_layerset_new')]
    #[ManagerRoute('/application/{slug}/layerset/{layersetId}/edit', methods: ['GET', 'POST'], name: 'mapbender_manager_layerset_edit')]
    public function edit(Request $request, $slug, $layersetId = null)
    {
        if ($layersetId) {
            $layerset = $this->requireLayerset($layersetId);
            $application = $layerset->getApplication();
        } else {
            $application = $this->requireDbApplication($slug);
            $layerset = new Layerset();
            $layerset->setApplication($application);
        }
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);

        $form = $this->createForm('Mapbender\CoreBundle\Form\Type\LayersetType', $layerset);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $application->setUpdated(new \DateTime('now'));
                $this->em->persist($application);
                $this->em->persist($layerset);
                $this->em->flush();
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
     * @param Request $request
     * @param string $slug
     * @param string $layersetId
     * @return Response
     */
    #[ManagerRoute('/application/{slug}/layerset/{layersetId}/delete', methods: ['GET', 'POST', 'DELETE'])]
    public function delete(Request $request, $slug, $layersetId)
    {
        $layerset = $this->requireLayerset($layersetId);
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        if ($request->getMethod() === Request::METHOD_GET) {
            // Render confirmation dialog content
            return $this->render('@MapbenderManager/Application/deleteLayerset.html.twig', array(
                'layerset' => $layerset,
            ));
        } else {
            if (!$this->isCsrfTokenValid('layerset_delete', $request->request->get('token'))) {
                throw new BadRequestHttpException();
            }

            $this->em->remove($layerset);
            $application->setUpdated(new \DateTime('now'));
            $this->em->persist($application);
            $this->em->flush();
            $this->addFlash('success', 'mb.layerset.remove.success');
            /** @todo: perform redirect server side, not client side */
            return new Response();
        }
    }

    /**
     * Setter action for "selected" flag (single value, immediate by ajax, no form)
     *
     * @param Request $request
     * @param Layerset $layerset
     * @return Response
     */
    #[ManagerRoute('/layerset/{layerset}/toggleselected', methods: ['POST'])]
    public function setselected(Request $request, Layerset $layerset)
    {
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);

        if (!$this->isCsrfTokenValid('layerset', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $layerset->setSelected($request->request->getBoolean('enabled'));
        $application->setUpdated(new \DateTime('now'));
        $this->em->persist($layerset);
        $this->em->persist($application);
        $this->em->flush();
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
