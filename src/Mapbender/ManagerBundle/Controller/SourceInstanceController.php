<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use FOM\ManagerBundle\Configuration\Route;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Entity\ReusableSourceInstanceAssignment;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceAssignment;
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Contracts\Translation\TranslatorInterface;

class SourceInstanceController extends ApplicationControllerBase
{
    /** @var TypeDirectoryService */
    protected $typeDirectory;

    protected TranslatorInterface $trans;


    public function __construct(TypeDirectoryService $typeDirectory, TranslatorInterface $trans)
    {
        $this->typeDirectory = $typeDirectory;
        $this->trans = $trans;
    }

    /**
     * @Route("/application/{slug}/instance/{instanceId}", name="mapbender_manager_repository_instance")
     * @Route("/instance/{instanceId}", name="mapbender_manager_repository_unowned_instance", requirements={"instanceId"="\d+"})
     * @Route("/instance/{instanceId}/layerset/{layerset}", name="mapbender_manager_repository_unowned_instance_scoped", requirements={"instanceId"="\d+"})
     * @param Request $request
     * @param string|null $slug
     * @param string $instanceId
     * @param Layerset|null $layerset
     * @return Response
     */
    public function editAction(Request $request, $instanceId, $slug = null, Layerset $layerset = null)
    {
        $em = $this->getEntityManager();
        /** @var SourceInstance|null $instance */
        $instance = $em->getRepository("MapbenderCoreBundle:SourceInstance")->find($instanceId);
        $applicationRepository = $this->getDbApplicationRepository();
        if (!$layerset) {
            if ($slug) {
                $application = $applicationRepository->findOneBy(array(
                    'slug' => $slug,
                ));
            } else {
                $application = null;
            }
        } else {
            $application = $layerset->getApplication();
        }
        /** @var Application|null $application */
        if ($application) {
            $this->denyAccessUnlessGranted('EDIT', $application);
        } else {
            $this->denyAccessUnlessGranted('EDIT', new ObjectIdentity('class', Source::class));
        }
        if (!$instance || ($application && !$application->getSourceInstances(true)->contains($instance))) {
            throw $this->createNotFoundException();
        }
        if (!$layerset && $application) {
            $layerset = $application->getLayersets()->filter(function ($layerset) use ($instance) {
                /** @var Layerset $layerset */
                return $layerset->getCombinedInstances()->contains($instance);
            })->first();
        }

        $factory = $this->typeDirectory->getInstanceFactory($instance->getSource());
        $form = $this->createForm($factory->getFormType($instance), $instance);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($instance);
            $dtNow = new \DateTime('now');
            foreach ($applicationRepository->findWithSourceInstance($instance) as $affectedApplication) {
                $em->persist($affectedApplication);
                $affectedApplication->setUpdated($dtNow);
            }
            $em->flush();

            $this->addFlash('success', $this->trans->trans('mb.manager.admin.instance.update_successful'));
            // redirect to self
            return $this->redirectToRoute($request->attributes->get('_route'), $request->attributes->get('_route_params'));
        } else if ($form->isSubmitted() && count($_POST, COUNT_RECURSIVE) >= ini_get("max_input_vars")) {
            $form->addError(new FormError($this->trans->trans('mb.manager.admin.instance.max_input_vars_exceeded')));
        }

        return $this->render($factory->getFormTemplate($instance), array(
            "form" => $form->createView(),
            "instance" => $form->getData(),
            'layerset' => $layerset,
            'edit_shared_instances' => $this->isGranted('EDIT', new ObjectIdentity('class', Source::class)),
        ));
    }

    /**
     * @Route("/instance/{instance}/delete", methods={"GET", "POST", "DELETE"})
     * @param Request $request
     * @param SourceInstance $instance
     * @return Response
     */
    public function deleteAction(Request $request, SourceInstance $instance)
    {
        /** @todo: specify / implement proper grants */
        $oid = new ObjectIdentity('class', Source::class);
        $this->denyAccessUnlessGranted('DELETE', $oid);

        // Use an empty form to help client code follow the final redirect properly
        // See Resources/public/confirm-delete.js
        $dummyForm = $this->createForm(FormType::class, null, array(
            'method' => 'DELETE',
            'action' => $this->generateUrl('mapbender_manager_sourceinstance_delete', array(
                'instance' => $instance,
            )),
        ));
        $dummyForm->handleRequest($request);

        if (!$request->isMethod(Request::METHOD_GET)) {
            if ($request->request->has('token')) {
                // Source instance within an application
                $csrfValid = $this->isCsrfTokenValid('layerset', $request->request->get('token'));
            } else {
                // Free Instance (from sources tab)
                $csrfValid = $dummyForm->isSubmitted() && $dummyForm->isValid();
            }

            if (!$csrfValid) {
                $this->addFlash('error', $this->trans->trans('mb.manager.admin.csrf_token_invalid'));
            } else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($instance);
                $em->flush();
            }

            if ($returnUrl = $request->query->get('return')) {
                return $this->redirect($returnUrl);
            } else {
                return $this->redirectToRoute('mapbender_manager_repository_index', array(
                    '_fragment' => 'tabSharedInstances',
                ));
            }
        } else {
            $viewData = $this->getApplicationRelationViewData($instance) + array(
                    'form' => $dummyForm->createView(),
                    'instance' => $instance,
                );
            return $this->render('@MapbenderManager/SourceInstance/confirmdelete.html.twig', $viewData);
        }
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @Route("/application/{slug}/layerset/{layersetId}/source/{sourceId}/add",
     *     name="mapbender_manager_application_addinstance",
     *     methods={"GET"})
     *
     * @param Request $request
     * @param string $slug of Application
     * @param int $layersetId
     * @param int $sourceId
     * @return Response
     */
    public function addInstanceAction(Request $request, $slug, $layersetId, $sourceId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var Application|null $application */
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if ($application) {
            $this->denyAccessUnlessGranted('EDIT', $application);
        } else {
            throw $this->createNotFoundException();
        }
        $layerset = $this->requireLayerset($layersetId, $application);
        /** @var Source|null $source */
        $source = $this->getDoctrine()->getRepository(Source::class)->find($sourceId);
        $newInstance = $this->typeDirectory->createInstance($source);
        foreach ($layerset->getCombinedInstanceAssignments()->getValues() as $index => $otherAssignment) {
            /** @var SourceInstanceAssignment $otherAssignment */
            $otherAssignment->setWeight($index + 1);
            $entityManager->persist($otherAssignment);
        }

        $newInstance->setWeight(0);
        $newInstance->setLayerset($layerset);
        $layerset->getInstances()->add($newInstance);

        $entityManager->persist($application);
        $application->setUpdated(new \DateTime('now'));

        $entityManager->flush();
        $this->addFlash('success', 'mb.source.instance.create.success');
        return $this->redirectToRoute("mapbender_manager_repository_instance", array(
            "slug" => $slug,
            "instanceId" => $newInstance->getId(),
        ));
    }

    /**
     * @Route("/instance/createshared/{source}", methods={"GET", "POST"}))
     * @param Request $request
     * @param Source $source
     * @return Response
     */
    public function createsharedAction(Request $request, Source $source)
    {
        $this->denyAccessUnlessGranted('EDIT', new ObjectIdentity('class', Source::class));
        // @todo: only act on post
        $em = $this->getDoctrine()->getManager();
        $instance = $this->typeDirectory->createInstance($source);
        $instance->setLayerset(null);
        $em->persist($instance);
        $em->flush();
        $this->addFlash('success', 'mb.manager.sourceinstance.created_reusable');
        return $this->redirectToRoute('mapbender_manager_repository_unowned_instance', array(
            'instanceId' => $instance->getId(),
        ));
    }

    /**
     * @Route("/instance/{instance}/promotetoshared",
     *        name="mapbender_manager_repository_promotetosharedinstance")
     * @param Request $request
     * @param SourceInstance $instance
     * @return Response
     */
    public function promotetosharedAction(Request $request, SourceInstance $instance)
    {
        $this->denyAccessUnlessGranted('EDIT', new ObjectIdentity('class', Source::class));
        $layerset = $instance->getLayerset();
        if (!$layerset) {
            throw new \LogicException("Instance is already shared");
        }
        $em = $this->getEntityManager();
        $assignment = new ReusableSourceInstanceAssignment();
        $assignment->setInstance($instance);

        $assignment->setWeight($instance->getWeight());
        $assignment->setEnabled($instance->getEnabled());
        $layerset->getInstances(false)->removeElement($instance);
        $instance->setLayerset(null);
        $assignment->setLayerset($layerset);
        $layerset->getReusableInstanceAssignments()->add($assignment);
        WeightSortedCollectionUtil::reassignWeights($layerset->getCombinedInstanceAssignments());
        $em->persist($layerset);
        $em->persist($instance);
        $layerset->getApplication()->setUpdated(new \DateTime('now'));
        $em->persist($layerset->getApplication());
        $em->flush();
        $this->addFlash('success', $this->trans->trans('mb.manager.admin.instance.converted_to_shared'));
        return $this->redirectToRoute('mapbender_manager_repository_instance', array(
            'instanceId' => $instance->getId(),
            'slug' => $layerset->getApplication()->getSlug(),
        ));
    }

    /**
     * @Route("/application/layerset/{layerset}/instance-enable/{instanceId}", methods={"POST"},
     *        name="mapbender_manager_repository_instanceenabled")
     * @param Request $request
     * @param Layerset $layerset
     * @param string $instanceId
     * @return Response
     */
    public function toggleEnabledAction(Request $request, Layerset $layerset, $instanceId)
    {
        /** @var SourceInstance|null $sourceInstance */
        $sourceInstance = $this->getDoctrine()->getRepository(SourceInstance::class)->find($instanceId);
        if (!$sourceInstance || !$layerset->getInstances()->contains($sourceInstance)) {
            throw $this->createNotFoundException();
        }
        return $this->toggleEnabledCommon($request, $layerset, $sourceInstance);
    }

    /**
     * @Route("/application/reusable-instance-enable/{assignmentId}", methods={"POST"},
     *        name="mapbender_manager_repository_instanceassignmentenabled")
     * @param Request $request
     * @param string $assignmentId
     * @return Response
     */
    public function toggleAssignmentEnabledAction(Request $request, $assignmentId)
    {
        /** @var ReusableSourceInstanceAssignment|null $assignment */
        $assignment = $this->getDoctrine()->getRepository(ReusableSourceInstanceAssignment::class)->find($assignmentId);
        if (!$assignment || !$assignment->getLayerset()) {
            throw $this->createNotFoundException();
        }
        $layerset = $assignment->getLayerset();
        return $this->toggleEnabledCommon($request, $layerset, $assignment);
    }

    /**
     * @param Request $request
     * @param Layerset $layerset
     * @param SourceInstanceAssignment $assignment
     * @return Response
     */
    protected function toggleEnabledCommon(Request $request, Layerset $layerset, SourceInstanceAssignment $assignment)
    {
        if (!$layerset->getApplication()) {
            throw $this->createNotFoundException();
        }
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $layerset->getApplication());

        if (!$this->isCsrfTokenValid('layerset', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $em = $this->getEntityManager();
        $newEnabled = $request->request->get('enabled') === 'true';
        $assignment->setEnabled($newEnabled);
        $application->setUpdated(new \DateTime('now'));
        $em->persist($application);
        $em->persist($assignment);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/application/{slug}/instance/{layersetId}/weight/{instanceId}",
     *        name="mapbender_manager_repository_instanceweight")
     * @param Request $request
     * @param string $slug
     * @param string $layersetId (unused, legacy)
     * @param string $instanceId
     * @return Response
     */
    public function weightAction(Request $request, $slug, $layersetId, $instanceId)
    {
        /** @var SourceInstance|null $instance */
        $instance = $this->getDoctrine()->getRepository(SourceInstance::class)->find($instanceId);

        if (!$instance) {
            throw $this->createNotFoundException('The source instance id:"' . $instanceId . '" does not exist.');
        }

        $layerset = $instance->getLayerset();
        return $this->instanceWeightCommon($request, $layerset, $instance);

    }

    /**
     * @Route("/layerset/{layerset}/reusable-weight/{assignmentId}",
     *        name="mapbender_manager_repository_assignmentweight")
     * @param Request $request
     * @param Layerset $layerset
     * @param string $assignmentId
     * @return Response
     */
    public function assignmentweightAction(Request $request, Layerset $layerset, $assignmentId)
    {
        /** @var ReusableSourceInstanceAssignment|null $assignment */
        $assignment = $this->getDoctrine()->getRepository(ReusableSourceInstanceAssignment::class)->find($assignmentId);
        if (!$assignment || !$assignment->getLayerset()) {
            throw $this->createNotFoundException();
        }
        return $this->instanceWeightCommon($request, $layerset, $assignment);
    }

    /**
     * @param Request $request
     * @param Layerset $layerset
     * @param SourceInstanceAssignment $assignment
     * @return Response
     */
    protected function instanceWeightCommon(Request $request, Layerset $layerset, SourceInstanceAssignment $assignment)
    {
        $em = $this->getEntityManager();
        $newWeight = $request->get("number");
        $targetLayersetId = $request->get("new_layersetId");

        $assignments = $layerset->getCombinedInstanceAssignments();
        $targetLayerset = $this->requireLayerset($targetLayersetId);

        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);

        if (!$this->isCsrfTokenValid('layerset', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        if ($layerset === $targetLayerset) {
            if (intval($newWeight) === $assignment->getWeight()) {
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
            }

            WeightSortedCollectionUtil::updateSingleWeight($assignments, $assignment, $newWeight);
        } else {
            $targetAssignments = $targetLayerset->getCombinedInstanceAssignments();
            WeightSortedCollectionUtil::moveBetweenCollections($targetAssignments, $assignments, $assignment, $newWeight);
            $assignment->setLayerset($targetLayerset);
            $em->persist($targetLayerset);
        }
        $em->persist($assignment);
        $em->persist($layerset);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param SourceInstance $instance
     * @return mixed[]
     */
    protected function getApplicationRelationViewData(SourceInstance $instance)
    {
        $applicationOrder = array(
            'title' => Criteria::ASC,
            'slug' => Criteria::ASC,
        );
        $viewData = array(
            'layerset_groups' => array(),
        );
        $applicationRepository = $this->getDbApplicationRepository();
        $relatedApplications = $applicationRepository->findWithSourceInstance($instance, null, $applicationOrder);
        foreach ($relatedApplications as $application) {
            /** @var Layerset[] $relatedLayersets */
            $relatedLayersets = $application->getLayersets()->filter(function ($layerset) use ($instance) {
                /** @var Layerset $layerset */
                return $layerset->getCombinedInstances()->contains($instance);
            })->getValues();
            if (!$relatedLayersets) {
                throw new \LogicException("Instance => Application lookup error; should contain instance #{$instance->getId()}, but doesn't");
            }
            $appViewData = array(
                'application' => $application,
                'instance_groups' => array(),
            );
            foreach ($relatedLayersets as $ls) {
                $layersetViewData = array(
                    'layerset' => $ls,
                );
                $assignments = $ls->getCombinedInstanceAssignments()->filter(function ($a) use ($instance) {
                    /** @var SourceInstanceAssignment $a */
                    return $a->getInstance() === $instance;
                });
                $layersetViewData['instance_assignments'] = $assignments;
                $appViewData['instance_groups'][] = $layersetViewData;
            }
            $viewData['layerset_groups'][] = $appViewData;
        }
        return $viewData;
    }

    /**
     * @return ApplicationRepository
     */
    protected function getDbApplicationRepository()
    {
        /** @var ApplicationRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Application::class);
        return $repository;
    }
}
