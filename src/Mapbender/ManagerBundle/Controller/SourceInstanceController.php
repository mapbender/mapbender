<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use FOM\ManagerBundle\Configuration\Route;
use FOM\UserBundle\Form\Type\PermissionListType;
use FOM\UserBundle\Security\Permission\PermissionManager;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Entity\Repository\SourceInstanceRepository;
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
use Symfony\Contracts\Translation\TranslatorInterface;

class SourceInstanceController extends ApplicationControllerBase
{
    public function __construct(
        protected TypeDirectoryService $typeDirectory,
        protected TranslatorInterface $trans,
        EntityManagerInterface $em,
        protected PermissionManager $permissionManager,
    )    {
        parent::__construct($em);
    }

    /**
     * @param Request $request
     * @param string|null $slug
     * @param string $instanceId
     * @param Layerset|null $layerset
     * @return Response
     */
    #[Route('/application/{slug}/instance/{instanceId}', name: 'mapbender_manager_repository_instance')]
    #[Route('/instance/{instanceId}', name: 'mapbender_manager_repository_unowned_instance', requirements: ['instanceId' => '\d+'])]
    #[Route('/instance/{instanceId}/layerset/{layerset}', name: 'mapbender_manager_repository_unowned_instance_scoped', requirements: ['instanceId' => '\d+'])]
    public function edit(Request $request, $instanceId, $slug = null, ?Layerset $layerset = null)
    {
        /** @var SourceInstance|null $instance */
        $instance = $this->em->getRepository(SourceInstance::class)->find($instanceId);
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
            $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        } else {
            $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES);
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
            $this->em->persist($instance);
            $dtNow = new \DateTime('now');
            foreach ($applicationRepository->findWithSourceInstance($instance) as $affectedApplication) {
                $this->em->persist($affectedApplication);
                $affectedApplication->setUpdated($dtNow);
            }
            $this->em->flush();

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
            'edit_shared_instances' => $this->isGranted(ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES),
        ));
    }

    /**
     * @param Request $request
     * @param SourceInstance $instance
     * @return Response
     */
    #[Route('/instance/{instance}/delete', methods: ['GET', 'POST', 'DELETE'])]
    public function delete(Request $request, SourceInstance $instance)
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_DELETE_SOURCES);

        // Use an empty form to help client code follow the final redirect properly
        // See Resources/public/confirm-delete.js
        $dummyForm = $this->createForm(FormType::class, null, array(
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
                $this->em->remove($instance);
                $this->em->flush();
                $this->addFlash('success', $this->trans->trans('mb.layerset.remove.success'));
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
     *
     */
    #[Route('/application/{slug}/layerset/{layersetId}/source/{sourceId}/add', name: 'mapbender_manager_application_addinstance', methods: ['GET'])]
    public function addInstance(string $slug, int $layersetId, int $sourceId): Response
    {
        /** @var Application|null $application */
        $application = $this->em->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if ($application) {
            $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        } else {
            throw $this->createNotFoundException();
        }
        $newInstance = $this->createNewSourceInstance($application, $sourceId, $layersetId, $this->em);
        $this->addFlash('success', 'mb.manager.source.instance.created');
        return $this->redirectToRoute("mapbender_manager_repository_instance", array(
            "slug" => $slug,
            "instanceId" => $newInstance->getId(),
        ));
    }

    /**
     * @param Request $request
     * @param Source $source
     * @return Response
     */
    #[Route('/instance/createshared/{source}', methods: ['GET', 'POST'])]
    public function createshared(Source $source)
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES);
        // @todo: only act on post
        $instance = $this->typeDirectory->getInstanceFactory($source)->createInstance($source, null);
        $instance->setLayerset(null);
        $this->em->persist($instance);
        $this->em->flush();
        $this->addFlash('success', 'mb.manager.source.instance.created_reusable');
        return $this->redirectToRoute('mapbender_manager_repository_unowned_instance', array(
            'instanceId' => $instance->getId(),
        ));
    }

    /**
     * @param Request $request
     * @param SourceInstance $instance
     * @return Response
     */
    #[Route('/instance/{instance}/promotetoshared', name: 'mapbender_manager_repository_promotetosharedinstance')]
    public function promotetoshared(SourceInstance $instance)
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES);
        $layerset = $instance->getLayerset();
        if (!$layerset) {
            throw new \LogicException("Instance is already shared");
        }
        $assignment = new ReusableSourceInstanceAssignment();
        $assignment->setInstance($instance);

        // shared instance must be enabled, the ReusableSourceInstanceAssignment determines enabled state for applications
        $assignment->setEnabled($instance->getEnabled());
        $instance->setEnabled(true);
        $assignment->setWeight($instance->getWeight());
        $layerset->getInstances(false)->removeElement($instance);
        $instance->setLayerset(null);
        $assignment->setLayerset($layerset);
        $layerset->getReusableInstanceAssignments()->add($assignment);
        WeightSortedCollectionUtil::reassignWeights($layerset->getCombinedInstanceAssignments());
        $this->em->persist($layerset);
        $this->em->persist($instance);
        $layerset->getApplication()->setUpdated(new \DateTime('now'));
        $this->em->persist($layerset->getApplication());
        $this->em->flush();
        $this->addFlash('success', $this->trans->trans('mb.manager.admin.instance.converted_to_shared'));
        return $this->redirectToRoute('mapbender_manager_repository_instance', array(
            'instanceId' => $instance->getId(),
            'slug' => $layerset->getApplication()->getSlug(),
        ));
    }

    /**
     * @param Request $request
     * @param Layerset $layerset
     * @param string $instanceId
     * @return Response
     */
    #[Route('/application/layerset/{layerset}/instance-enable/{instanceId}', methods: ['POST'], name: 'mapbender_manager_repository_instanceenabled')]
    public function toggleEnabled(Request $request, Layerset $layerset, $instanceId)
    {
        /** @var SourceInstance|null $sourceInstance */
        $sourceInstance = $this->em->getRepository(SourceInstance::class)->find($instanceId);
        if (!$sourceInstance || !$layerset->getInstances()->contains($sourceInstance)) {
            throw $this->createNotFoundException();
        }
        return $this->toggleEnabledCommon($request, $layerset, $sourceInstance);
    }

    #[Route('/application/reusable-instance-enable/{assignmentId}', methods: ['POST'], name: 'mapbender_manager_repository_instanceassignmentenabled')]
    public function toggleAssignmentEnabled(Request $request, $assignmentId): Response
    {
        /** @var ReusableSourceInstanceAssignment|null $assignment */
        $assignment = $this->em->getRepository(ReusableSourceInstanceAssignment::class)->find($assignmentId);
        if (!$assignment || !$assignment->getLayerset()) {
            throw $this->createNotFoundException();
        }
        $layerset = $assignment->getLayerset();
        return $this->toggleEnabledCommon($request, $layerset, $assignment);
    }

    #[Route('/application/layerset/{layersetId}/instance/{instanceId}/security', name: 'mapbender_manager_instance_security', requirements: ['layersetId' => '\d+', 'instanceId' => '\d+'], methods: ['GET', 'POST'])]
    public function securitySourceInstance(Request $request, int $layersetId, int $instanceId)
    {
        /** @var ?SourceInstance $instance */
        $instance = $this->em->getRepository(SourceInstance::class)->find($instanceId);
        if (!$instance) {
            throw $this->createNotFoundException("The source instance with the id \"$instanceId\" does not exist.");
        }

        if ($instance->getLayerset()->getId() != $layersetId) {
            throw $this->createNotFoundException("The source instance with the id \"$instanceId\" is not part of the layerset with the id \"$layersetId\".");
        }

        return $this->security($request, $instance, $layersetId);
    }

    #[Route('/application/layerset/{layersetId}/sharedinstance/{assignmentId}/security', name: 'mapbender_manager_sharedinstance_security', requirements: ['layersetId' => '\d+', 'assignmentId' => '\d+'], methods: ['GET', 'POST'])]
    public function securitySharedInstance(Request $request, int $layersetId, int $assignmentId)
    {
        /** @var ?ReusableSourceInstanceAssignment $instance */
        $instance = $this->em->getRepository(ReusableSourceInstanceAssignment::class)->find($assignmentId);
        if (!$instance) {
            throw $this->createNotFoundException("The source instance with the id \"$assignmentId\" does not exist.");
        }

        return $this->security($request, $instance, $layersetId);
    }

    private function security(Request $request, SourceInstance|ReusableSourceInstanceAssignment $instance, int $layersetId)
    {
        if ($instance->getLayerset()->getId() != $layersetId) {
            throw $this->createNotFoundException("The source instance with the id \"{$instance->getId()}\" is not part of the layerset with the id \"$layersetId\".");
        }

        $application = $instance->getLayerset()->getApplication();
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);

        $form = $this->createForm(FormType::class, null, array(
            'label' => false,
        ));
        $resourceDomain = $this->permissionManager->findResourceDomainFor($instance, throwIfNotFound: true);
        $form->add('security', PermissionListType::class, [
            'resource_domain' => $resourceDomain,
            'resource' => $instance,
            'entry_options' => [
                'resource_domain' => $resourceDomain,
            ],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->beginTransaction();
            try {
                $application->setUpdated(new \DateTime('now'));
                $this->em->persist($application);
                if ($form->has('security')) {
                    $this->permissionManager->savePermissions($instance, $form->get('security')->getData());
                }
                $this->em->flush();
                $this->em->commit();
                $this->addFlash('success', "Your element's access has been changed.");
            } catch (\Exception $e) {
                $this->addFlash('error', "There was an error trying to change your element's access.");
                $this->em->rollback();
                $this->em->close();
            }
            return $this->redirectToRoute('mapbender_manager_application_edit', array(
                'slug' => $application->getSlug(),
                '_fragment' => 'tabLayers',
            ));
        }
        return $this->render('@MapbenderManager/Element/security.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    protected function toggleEnabledCommon(Request $request, Layerset $layerset, null|ReusableSourceInstanceAssignment|SourceInstanceAssignment $assignment): Response
    {
        if (!$layerset->getApplication()) {
            throw $this->createNotFoundException();
        }
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $layerset->getApplication());

        if (!$this->isCsrfTokenValid('layerset', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $newEnabled = $request->request->get('enabled') === 'true';
        $assignment->setEnabled($newEnabled);
        $application->setUpdated(new \DateTime('now'));
        $this->em->persist($application);
        $this->em->persist($assignment);
        $this->em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param string $slug
     * @param string $layersetId (unused, legacy)
     * @param string $instanceId
     * @return Response
     */
    #[Route('/application/{slug}/instance/{layersetId}/weight/{instanceId}', name: 'mapbender_manager_repository_instanceweight')]
    public function weight(Request $request, $slug, $layersetId, $instanceId)
    {
        /** @var SourceInstance|null $instance */
        $instance = $this->em->getRepository(SourceInstance::class)->find($instanceId);

        if (!$instance) {
            throw $this->createNotFoundException('The source instance id:"' . $instanceId . '" does not exist.');
        }

        $layerset = $instance->getLayerset();
        return $this->instanceWeightCommon($request, $layerset, $instance);

    }

    /**
     * @param Request $request
     * @param Layerset $layerset
     * @param string $assignmentId
     * @return Response
     */
    #[Route('/layerset/{layerset}/reusable-weight/{assignmentId}', name: 'mapbender_manager_repository_assignmentweight')]
    public function assignmentweight(Request $request, Layerset $layerset, $assignmentId)
    {
        /** @var ReusableSourceInstanceAssignment|null $assignment */
        $assignment = $this->em->getRepository(ReusableSourceInstanceAssignment::class)->find($assignmentId);
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
    protected function instanceWeightCommon(Request $request, Layerset $layerset, ReusableSourceInstanceAssignment|SourceInstanceAssignment $assignment)
    {
        $newWeight = $request->get("number");
        $targetLayersetId = $request->get("new_layersetId");

        $assignments = $layerset->getCombinedInstanceAssignments();
        $targetLayerset = $this->requireLayerset($targetLayersetId);

        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);

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
            $this->em->persist($targetLayerset);
        }
        $this->em->persist($assignment);
        $this->em->persist($layerset);
        $this->em->flush();

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
        $repository = $this->em->getRepository(Application::class);
        return $repository;
    }

    public function createNewSourceInstance(Application $application, int $sourceId, int $layersetId, ObjectManager $entityManager, $options = []): SourceInstance
    {
        $layerset = $this->requireLayerset($layersetId, $application);
        /** @var Source|null $source */
        $source = $this->em->getRepository(Source::class)->find($sourceId);
        $newInstance = $this->typeDirectory->getInstanceFactory($source)->createInstance($source, $options);
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
        return $newInstance;
    }
}
