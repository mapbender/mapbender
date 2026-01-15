<?php

namespace Mapbender\ManagerBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Form\Type\PermissionListType;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use FOM\UserBundle\Security\Permission\PermissionManager;
use FOM\UserBundle\Security\Permission\SubjectDomainPublic;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Entity\Repository\SourceInstanceRepository;
use Mapbender\CoreBundle\Entity\ReusableSourceInstanceAssignment;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceAssignment;
use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Mapbender\ManagerBundle\Component\UploadScreenshot;
use Mapbender\ManagerBundle\Form\Type\ApplicationType;
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Mapbender application management
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmitd <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class ApplicationController extends ApplicationControllerBase
{
    public function __construct(protected ApplicationTemplateRegistry $templateRegistry,
                                protected UploadsManager              $uploadsManager,
                                protected PermissionManager           $permissionManager,
                                protected bool                        $enableResponsiveElements,
                                EntityManagerInterface $em,
                                protected FormFactory $formFactory,
                                protected UsageTrackingTokenStorage $usageTrackingTokenStorage,
    )
    {
        parent::__construct($em);
    }

    /**
     * Render a list of applications the current logged-in user has access to.
     *
     * @param Request $request
     * @return Response
     */
    #[ManagerRoute('/applications', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('mapbender_core_welcome_list');
    }

    /**
     * Shows form for creating new applications
     */
    #[ManagerRoute('/application/new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $application = new Application();
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS);

        $form = $this->createApplicationForm($application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $appDirectory = $this->uploadsManager->getSubdirectoryPath($application->getSlug(), true);
            } catch (IOException $e) {
                $this->addFlash('error', 'mb.application.create.failure.create.directory');
                return $this->redirectToRoute('mapbender_manager_application_index');
            }
            $application->setUpdated(new \DateTime('now'));

            $this->em->beginTransaction();
            $this->em->persist($application);
            $this->em->flush();
            if ($form->has('security')) {
                $this->permissionManager->savePermissions($application, $form->get('security')->getData());
            }
            $user = $this->getUser();
            if ($user instanceof User) {
                // grant all rights to the user that created the application
                $this->permissionManager->grant($user, $application, ResourceDomainApplication::ACTION_MANAGE_PERMISSIONS);
            }
            $scFile = $form->get('screenshotFile')->getData();

            if ($scFile && !$form->get('removeScreenShot')->getData()) {
                $uploadScreenShot = new UploadScreenshot();
                $uploadScreenShot->upload($appDirectory, $scFile, $application);
            }

            $this->em->persist($application);
            $this->em->flush();
            $this->createRegionProperties($application);

            $this->em->persist($application);
            $this->em->flush();
            $this->em->commit();
            $this->addFlash('success', 'mb.application.create.success');

            return $this->redirectToRoute('mapbender_manager_application_edit', array(
                'slug' => $application->getSlug(),
            ));
        }

        return $this->render('@MapbenderManager/Application/edit.html.twig', array(
            'application' => $application,
            'form' => $form->createView(),
            'edit_shared_instances' => $this->isGranted(ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES),
        ));
    }

    /**
     * Edit application
     *
     * @param string $slug Application name
     */
    #[ManagerRoute('/application/{slug}/edit', requirements: ['slug' => '[\w-]+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, $slug): Response
    {
        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);

        $oldSlug = $application->getSlug();

        $form = $this->createApplicationForm($application);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->beginTransaction();
            $application->setUpdated(new \DateTime('now'));
            if ($form->get('removeScreenShot')->getData() == '1') {
                $application->setScreenshot(null);
            }
            $this->em->persist($application);
            $this->em->flush();

            try {
                if ($oldSlug !== $application->getSlug()) {
                    $uploadPath = $this->uploadsManager->renameSubdirectory($oldSlug, $application->getSlug(), true);
                } else {
                    $uploadPath = $this->uploadsManager->getSubdirectoryPath($application->getSlug(), true);
                }
                $scFile = $form->get('screenshotFile')->getData();
                if ($scFile && !$form->get('removeScreenShot')->getData()) {
                    $uploadScreenShot = new UploadScreenshot();
                    $uploadScreenShot->upload($uploadPath, $scFile, $application);
                }
                $this->em->persist($application);
                $this->em->flush();
                if ($form->has('security')) {
                    $this->permissionManager->savePermissions($application, $form->get('security')->getData());
                }
                $this->em->commit();
                $this->addFlash('success', 'mb.application.save.success');
                return $this->redirectToRoute('mapbender_manager_application_edit', array(
                    'slug' => $application->getSlug(),
                ));
            } catch (IOException $e) {
                $this->addFlash('error', 'mb.application.save.failure.create.directory');
                $this->addFlash('error', ": {$e->getMessage()}");
                $this->em->rollback();
            } catch (\Exception $e) {
                $this->addFlash('error', 'mb.application.save.failure.general');
                $this->em->rollback();
            }
        }
        $template = $this->templateRegistry->getApplicationTemplate($application);
        if (!$template) {
            throw new \Exception("The requested template " . $application->getTemplate() . " is not available.");
        }

        // restore old slug to keep urls working
        $application->setSlug($oldSlug);
        return $this->render('@MapbenderManager/Application/edit.html.twig', array(
            'application' => $application,
            'regions' => $template->getRegions(),
            'form' => $form->createView(),
            'template_name' => $template->getTitle(),
            'edit_shared_instances' => $this->isGranted(ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES),
        ));
    }

    /**
     * Toggle application state.
     *
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    #[ManagerRoute('/application/{slug}/state', options: ['expose' => true], methods: ['POST'])]
    public function toggleState(Request $request, $slug)
    {
        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_MANAGE_PERMISSIONS, $application);

        if (!$this->isCsrfTokenValid('application_edit', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $requestedState = $request->request->get("enabled") === "true";
        $this->permissionManager->grant(SubjectDomainPublic::SLUG, $application, ResourceDomainApplication::ACTION_VIEW, $requestedState);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete application
     *
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    #[ManagerRoute('/application/{slug}/delete', requirements: ['slug' => '[\w-]+'], methods: ['POST'])]
    public function delete(Request $request, $slug)
    {
        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_DELETE, $application);

        if (!$this->isCsrfTokenValid('application_delete', $request->request->get('token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return new Response();
        }

        try {
            $this->em->beginTransaction();
            $this->em->remove($application);
            $this->em->flush();
            $this->em->commit();
            $this->uploadsManager->removeSubdirectory($slug);
            $this->addFlash('success', 'mb.application.remove.success');
        } catch (IOException $e) {
            $this->addFlash('error', 'mb.application.failure.remove.directory');
        } catch (\Exception $e) {
            $this->addFlash('error', 'mb.application.remove.failure.general');
        }

        return new Response();
    }

    /**
     *
     * @param string $slug of Application
     * @param int $layersetId
     * @return Response
     */
    #[ManagerRoute('/application/{slug}/layerset/{layersetId}/list', methods: ['GET'])]
    public function listSources($slug, $layersetId)
    {
        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES);

        $layerset = $this->requireLayerset($layersetId, $application);
        $sources = $this->em->getRepository(Source::class)->findBy(array(), array(
            'title' => 'ASC',
            'id' => 'ASC',
        ));
        /** @var SourceInstanceRepository $instanceRepository */
        $instanceRepository = $this->em->getRepository(SourceInstance::class);

        return $this->render('@MapbenderManager/Application/list-source.html.twig', array(
            'application' => $application,
            'layerset' => $layerset,
            'sources' => $sources,
            'reusable_instances' => $instanceRepository->findReusableInstances(array(), array(
                'title' => 'ASC',
                'id' => 'ASC',
            )),
        ));
    }

    /**
     * @param Request $request
     * @param SourceInstance $instance
     * @param Layerset $layerset
     * @return Response
     */
    #[ManagerRoute('/instance/{instance}/copy-into-layerset/{layerset}', methods: ['GET'])]
    public function sharedinstancecopy(SourceInstance $instance, Layerset $layerset)
    {
        if ($instance->getLayerset()) {
            throw new \LogicException("Instance is already owned by a Layerset");
        }
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        $instanceCopy = clone $instance;
        $this->em->persist($instanceCopy);
        $instanceCopy->setLayerset($layerset);
        $instanceCopy->setWeight(-1);
        $layerset->addInstance($instanceCopy);
        /**
         * remove original shared instance from layerset
         * @todo: finding the right assignment requires more information than is currently passed on by
         * @see RepositoryController::instanceAction. We simply remove all assignments of the instance.
         */
        $reusablePartitions = $layerset->getReusableInstanceAssignments()->partition(function ($_, $assignment) use ($instance) {
            /** @var SourceInstanceAssignment $assignment */
            return $assignment->getInstance() !== $instance;
        });
        foreach ($reusablePartitions[1] as $removableAssignment) {
            /** @var SourceInstanceAssignment $removableAssignment */
            $instanceCopy->setEnabled($removableAssignment->getEnabled());
            $this->em->remove($removableAssignment);
            $assignmentWeight = $removableAssignment->getWeight();
            if ($instanceCopy->getWeight() < 0 && $assignmentWeight >= 0) {
                $instanceCopy->setWeight($assignmentWeight);
            }
        }
        $layerset->setReusableInstanceAssignments($reusablePartitions[0]);
        WeightSortedCollectionUtil::reassignWeights($layerset->getCombinedInstanceAssignments());
        $this->em->persist($layerset);
        $this->em->persist($application);
        $application->setUpdated(new \DateTime('now'));
        $this->em->flush();
        $this->addFlash('success', 'mb.manager.source.instance.converted_to_bound');
        return $this->redirectToRoute('mapbender_manager_repository_instance', array(
            "slug" => $application->getSlug(),
            "instanceId" => $instanceCopy->getId(),
        ));
    }

    /**
     * @param Request $request
     * @param Layerset $layerset
     * @param SourceInstance $instance
     * @return Response
     */
    #[ManagerRoute('/instance/{instance}/attach/{layerset}')]
    public function attachreusableinstance(Layerset $layerset, SourceInstance $instance)
    {
        if ($instance->getLayerset()) {
            throw new \LogicException("Keine freie Instanz");
        }
        $application = $layerset->getApplication();
        $assignment = new ReusableSourceInstanceAssignment();
        $assignment->setLayerset($layerset);
        $assignment->setInstance($instance);
        $assignment->setWeight(0);

        foreach ($layerset->getCombinedInstanceAssignments()->getValues() as $index => $otherAssignment) {
            /** @var SourceInstanceAssignment $otherAssignment */
            $otherAssignment->setWeight($index + 1);
            $this->em->persist($otherAssignment);
        }

        $layerset->getReusableInstanceAssignments()->add($assignment);
        $this->em->persist($assignment);
        $this->em->persist($application);
        $application->setUpdated(new \DateTime('now'));
        $this->em->persist($layerset);
        // sanity
        $instance->setLayerset(null);
        $this->em->flush();
        $this->addFlash('success', 'mb.manager.source.instance.reusable_assigned_to_application');
        return $this->redirectToRoute("mapbender_manager_repository_instance", array(
            "slug" => $application->getSlug(),
            "instanceId" => $instance->getId(),
        ));
    }

    /**
     * Delete a source instance from a layerset
     *
     * @param string $slug
     * @param int $layersetId
     * @param int $instanceId
     * @return Response
     * @throws \Exception
     */
    #[ManagerRoute('/application/{slug}/layerset/{layersetId}/instance/{instanceId}/delete', methods: ['POST'])]
    public function deleteInstance(Request $request, $slug, $layersetId, $instanceId)
    {
        $application = $this->em->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if ($application) {
            $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        }

        if (!$this->isCsrfTokenValid('layerset', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $layerset = $this->requireLayerset($layersetId, $application);
        $instanceCriteria = new Criteria(Criteria::expr()->eq('id', $instanceId));
        $instance = $layerset->getInstances()->matching($instanceCriteria)->first();
        if (!$instance) {
            throw $this->createNotFoundException();
        }
        $this->em->persist($application);
        $application->setUpdated(new \DateTime('now'));
        $layerset->getInstances()->removeElement($instance);
        foreach ($layerset->getCombinedInstanceAssignments()->getValues() as $index => $remainingAssignment) {
            /** @var SourceInstanceAssignment $remainingAssignment */
            $remainingAssignment->setWeight($index);
            $this->em->persist($remainingAssignment);
        }

        $this->em->remove($instance);
        $this->em->flush();
        $this->addFlash('success', 'Your source instance has been deleted');
        return new Response();  // ???
    }

    /**
     * Remove a reusable source instance assigment
     *
     * @param Layerset $layerset
     * @param string $assignmentId
     * @return Response
     */
    #[ManagerRoute('/layerset/{layerset}/instance-assignment/{assignmentId}/detach', methods: ['POST'])]
    public function detachinstance(Request $request, Layerset $layerset, $assignmentId)
    {
        $application = $layerset->getApplication();
        $assignment = $layerset->getReusableInstanceAssignments()->filter(function ($assignment) use ($assignmentId) {
            /** @var ReusableSourceInstanceAssignment $assignment */
            return $assignment->getId() == $assignmentId;
        })->first();
        if (!$assignment || !$application) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);

        if (!$this->isCsrfTokenValid('layerset', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $layerset->getReusableInstanceAssignments()->removeElement($assignment);
        $this->em->remove($assignment);
        WeightSortedCollectionUtil::reassignWeights($layerset->getCombinedInstanceAssignments());
        $application->setUpdated(new \DateTime('now'));
        $this->em->persist($application);
        $this->em->persist($layerset);
        $this->em->flush();
        $this->addFlash('success', 'Your reusable source instance assignment has been deleted');
        $params = array(
            'slug' => $application->getSlug(),
        );
        return $this->redirectToRoute('mapbender_manager_application_edit', $params, Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param Application $application
     * @param string $regionName
     */
    #[ManagerRoute('/{application}/regionproperties/{regionName}', methods: ['POST'])]
    public function updateregionproperties(Request $request, Application $application, $regionName)
    {
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        // Provided by AbstractController
        /** @see \Symfony\Bundle\FrameworkBundle\Controller\AbstractController::getSubscribedServices() */
        $formBuilder = $this->formFactory->createNamedBuilder('application', 'Symfony\Component\Form\Extension\Core\Type\FormType', $application);
        $formBuilder->add('regionProperties', 'Mapbender\ManagerBundle\Form\Type\Application\RegionPropertiesType', array(
            'application' => $application,
            'region_names' => array($regionName),
        ));
        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($application);
            $application->setUpdated(new \DateTime());
            $this->em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } else {
            return new JsonResponse(\strval($form->getErrors()), Response::HTTP_BAD_REQUEST);
        }
    }

    private function createApplicationForm(Application $application): FormInterface
    {
        $form = $this->createForm(ApplicationType::class, $application);
        if ($this->allowPermissionEditing($application)) {
            $this->permissionManager->addFormType($form, $application, ['show_public_access' => true]);
        }
        return $form;
    }

    protected function allowPermissionEditing(Application $application): bool
    {
        return !$application->getId() // current user will become owner of the new application
            || $this->isGranted(ResourceDomainApplication::ACTION_MANAGE_PERMISSIONS, $application)
            || $this->isGranted(ResourceDomainInstallation::ACTION_EDIT_ALL_APPLICATIONS);
    }

    /**
     * Create initial application region properties
     *
     * @param Application $application
     */
    protected function createRegionProperties(Application $application)
    {
        $template = $this->templateRegistry->getApplicationTemplate($application);
        $templateProps = $template->getRegionsProperties();

        foreach ($templateProps as $regionName => $choices) {
            if (!$choices) {
                continue;
            }
            $propsEntity = new RegionProperties();
            $propsEntity->setApplication($application);
            $propsEntity->setName($regionName);
            $choiceKeys = array_keys($choices);
            $firstChoiceValues = $choices[$choiceKeys[0]];
            unset($firstChoiceValues['label']);
            $propsEntity->setProperties($firstChoiceValues);
            $application->addRegionProperties($propsEntity);
        }
    }

    /**
     * @return TokenInterface|null
     */
    protected function getUserToken()
    {
        // Provided by AbstractController
        /** @see \Symfony\Bundle\FrameworkBundle\Controller\AbstractController::getSubscribedServices() */
        return $this->usageTrackingTokenStorage->getToken();
    }
}
