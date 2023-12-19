<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Component\AclManager;
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
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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
    /** @var MutableAclProviderInterface */
    protected $aclProvider;
    /** @var ApplicationTemplateRegistry  */
    protected $templateRegistry;
    /** @var AclManager */
    protected $aclManager;
    /** @var UploadsManager */
    protected $uploadsManager;
    protected $enableResponsiveElements;

    public function __construct(MutableAclProviderInterface $aclProvider,
                                ApplicationTemplateRegistry $templateRegistry,
                                AclManager $aclManager,
                                UploadsManager $uploadsManager,
                                $enableResponsiveElements)
    {
        $this->aclProvider = $aclProvider;
        $this->templateRegistry = $templateRegistry;
        $this->aclManager = $aclManager;
        $this->uploadsManager = $uploadsManager;
        $this->enableResponsiveElements = $enableResponsiveElements;
    }

    /**
     * Render a list of applications the current logged in user has access to.
     *
     * @ManagerRoute("/applications", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        return $this->redirectToRoute('mapbender_core_welcome_list');
    }

    /**
     * Shows form for creating new applications
     *
     * @ManagerRoute("/application/new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function newAction(Request $request)
    {
        $application = new Application();
        $oid = new ObjectIdentity('class', get_class($application));
        $this->denyAccessUnlessGranted('CREATE', $oid);

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
            $em = $this->getEntityManager();

            $em->beginTransaction();
            $em->persist($application);
            $em->flush();
            if ($form->has('acl')) {
                $this->aclManager->setObjectACEs($application, $form->get('acl')->getData());
            }
            $scFile = $form->get('screenshotFile')->getData();

            if ($scFile && !$form->get('removeScreenShot')->getData()) {
                $uploadScreenShot = new UploadScreenshot();
                $uploadScreenShot->upload($appDirectory, $scFile, $application);
            }

            $em->persist($application);
            $em->flush();
            $this->createRegionProperties($application);

            $em->persist($application);
            $em->flush();
            $em->commit();
            $this->addFlash('success', 'mb.application.create.success');

            return $this->redirectToRoute('mapbender_manager_application_edit', array(
                'slug' => $application->getSlug(),
            ));
        }

        return $this->render('@MapbenderManager/Application/edit.html.twig', array(
            'application'         => $application,
            'form'                => $form->createView(),
            'edit_shared_instances' => $this->isGranted('EDIT', new ObjectIdentity('class', Source::class)),
        ));
    }

    /**
     * Edit application
     *
     * @ManagerRoute("/application/{slug}/edit", requirements = { "slug" = "[\w-]+" }, methods={"GET", "POST"})
     * @param $request Request
     * @param string $slug Application name
     * @return Response
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function editAction(Request $request, $slug)
    {
        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);

        $oldSlug          = $application->getSlug();

        $form             = $this->createApplicationForm($application);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getEntityManager();
            $em->beginTransaction();
            $application->setUpdated(new \DateTime('now'));
            if ($form->get('removeScreenShot')->getData() == '1') {
                $application->setScreenshot(null);
            }
            $em->persist($application);
            $em->flush();

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
                $em->persist($application);
                $em->flush();
                if ($form->has('acl')) {
                    $this->aclManager->setObjectACEs($application, $form->get('acl')->getData());
                }
                $em->commit();
                $this->addFlash('success', 'mb.application.save.success');
                return $this->redirectToRoute('mapbender_manager_application_edit', array(
                    'slug' => $application->getSlug(),
                ));
            } catch (IOException $e) {
                $this->addFlash('error', 'mb.application.save.failure.create.directory');
                $this->addFlash('error', ": {$e->getMessage()}");
                $em->rollback();
            } catch (\Exception $e) {
                $this->addFlash('error', 'mb.application.save.failure.general');
                $em->rollback();
            }
        }
        $template = $this->templateRegistry->getApplicationTemplate($application);

        // restore old slug to keep urls working
        $application->setSlug($oldSlug);
        return $this->render('@MapbenderManager/Application/edit.html.twig', array(
            'application'         => $application,
            'regions' => $template->getRegions(),
            'form'                => $form->createView(),
            'template_name' => $template->getTitle(),
            // Allow screenType filtering only on current map engine
            'allow_screentypes' => $this->enableResponsiveElements && $application->getMapEngineCode() !== Application::MAP_ENGINE_OL2,
            'edit_shared_instances' => $this->isGranted('EDIT', new ObjectIdentity('class', Source::class)),
        ));
    }

    /**
     * Toggle application state.
     *
     * @ManagerRoute("/application/{slug}/state", options={"expose"=true}, methods={"POST"})
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function toggleStateAction(Request $request, $slug)
    {
        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);

        if (!$this->isCsrfTokenValid('application_edit', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $em = $this->getEntityManager();

        $requestedState = $request->request->get("enabled") === "true";
        $application->setPublished($requestedState);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete application
     *
     * @ManagerRoute("/application/{slug}/delete", requirements = { "slug" = "[\w-]+" }, methods={"POST"})
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function deleteAction(Request $request, $slug)
    {
        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted('DELETE', $application);

        if (!$this->isCsrfTokenValid('application_delete', $request->request->get('token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return new Response();
        }

        try {
            $em = $this->getEntityManager();
            $em->beginTransaction();
            $this->aclProvider->deleteAcl(ObjectIdentity::fromDomainObject($application));
            $em->remove($application);
            $em->flush();
            $em->commit();
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
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/list", methods={"GET"})
     *
     * @param string $slug of Application
     * @param int $layersetId
     * @return Response
     */
    public function listSourcesAction($slug, $layersetId)
    {
        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        $sourceOid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('VIEW', $sourceOid);

        $layerset = $this->requireLayerset($layersetId, $application);
        $sources = $this->getDoctrine()->getRepository('MapbenderCoreBundle:Source')->findBy(array(), array(
            'title' => 'ASC',
            'id' => 'ASC',
        ));
        /** @var SourceInstanceRepository $instanceRepository */
        $instanceRepository = $this->getDoctrine()->getRepository(SourceInstance::class);

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
     * @ManagerRoute("/instance/{instance}/copy-into-layerset/{layerset}", methods={"GET"})
     * @param Request $request
     * @param SourceInstance $instance
     * @param Layerset $layerset
     * @return Response
     */
    public function sharedinstancecopyAction(Request $request, SourceInstance $instance, Layerset $layerset)
    {
        if ($instance->getLayerset()) {
            throw new \LogicException("Instance is already owned by a Layerset");
        }
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);
        $em = $this->getEntityManager();
        $instanceCopy = clone $instance;
        $em->persist($instanceCopy);
        $instanceCopy->setLayerset($layerset);
        $instanceCopy->setWeight(-1);
        $layerset->addInstance($instanceCopy);
        /**
         * remove original shared instance from layerset
         * @todo: finding the right assignment requires more information than is currently passed on by
         * @see RepositoryController::instanceAction. We simply remove all assignments of the instance.
         */
        $reusablePartitions = $layerset->getReusableInstanceAssignments()->partition(function($_, $assignment) use ($instance) {
            /** @var SourceInstanceAssignment $assignment */
            return $assignment->getInstance() !== $instance;
        });
        foreach ($reusablePartitions[1] as $removableAssignment) {
            /** @var SourceInstanceAssignment $removableAssignment */
            $em->remove($removableAssignment);
            $assignmentWeight = $removableAssignment->getWeight();
            if ($instanceCopy->getWeight() < 0 && $assignmentWeight >= 0) {
                $instanceCopy->setWeight($assignmentWeight);
            }
        }
        $layerset->setReusableInstanceAssignments($reusablePartitions[0]);
        WeightSortedCollectionUtil::reassignWeights($layerset->getCombinedInstanceAssignments());
        $em->persist($layerset);
        $em->persist($application);
        $application->setUpdated(new \DateTime('now'));
        $em->flush();
        $this->addFlash('success', 'mb.manager.sourceinstance.converted_to_bound');
        return $this->redirectToRoute('mapbender_manager_repository_instance', array(
            "slug" => $application->getSlug(),
            "instanceId" => $instanceCopy->getId(),
        ));
    }

    /**
     * @ManagerRoute("/instance/{instance}/attach/{layerset}")
     * @param Request $request
     * @param Layerset $layerset
     * @param SourceInstance $instance
     * @return Response
     */
    public function attachreusableinstanceAction(Request $request, Layerset $layerset, SourceInstance $instance)
    {
        if ($instance->getLayerset()) {
            throw new \LogicException("Keine freie Instanz");
        }
        $em = $this->getEntityManager();
        $application = $layerset->getApplication();
        $assignment = new ReusableSourceInstanceAssignment();
        $assignment->setLayerset($layerset);
        $assignment->setInstance($instance);
        $assignment->setWeight(0);

        foreach ($layerset->getCombinedInstanceAssignments()->getValues() as $index => $otherAssignment) {
            /** @var SourceInstanceAssignment $otherAssignment */
            $otherAssignment->setWeight($index + 1);
            $em->persist($otherAssignment);
        }

        $layerset->getReusableInstanceAssignments()->add($assignment);
        $em->persist($assignment);
        $em->persist($application);
        $application->setUpdated(new \DateTime('now'));
        $em->persist($layerset);
        // sanity
        $instance->setLayerset(null);
        $em->flush();
        $this->addFlash('success', 'mb.manager.sourceinstance.reusable_assigned_to_application');
        return $this->redirectToRoute("mapbender_manager_repository_instance", array(
            "slug" => $application->getSlug(),
            "instanceId" => $instance->getId(),
        ));
    }

    /**
     * Delete a source instance from a layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/instance/{instanceId}/delete", methods={"POST"})
     *
     * @param string $slug
     * @param int   $layersetId
     * @param int   $instanceId
     * @return Response
     * @throws \Exception
     */
    public function deleteInstanceAction(Request $request, $slug, $layersetId, $instanceId)
    {
        $em = $this->getEntityManager();
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if ($application) {
            $this->denyAccessUnlessGranted('EDIT', $application);
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
        $em->persist($application);
        $application->setUpdated(new \DateTime('now'));
        $layerset->getInstances()->removeElement($instance);
        foreach ($layerset->getCombinedInstanceAssignments()->getValues() as $index => $remainingAssignment) {
            /** @var SourceInstanceAssignment $remainingAssignment */
            $remainingAssignment->setWeight($index);
            $em->persist($remainingAssignment);
        }

        $em->remove($instance);
        $em->flush();
        $this->addFlash('success', 'Your source instance has been deleted');
        return new Response();  // ???
    }

    /**
     * Remove a reusable source instance assigment
     *
     * @ManagerRoute("/layerset/{layerset}/instance-assignment/{assignmentId}/detach", methods={"POST"})
     * @param Layerset $layerset
     * @param string $assignmentId
     * @return Response
     */
    public function detachinstanceAction(Request $request, Layerset $layerset, $assignmentId)
    {
        $application = $layerset->getApplication();
        $assignment = $layerset->getReusableInstanceAssignments()->filter(function ($assignment) use ($assignmentId) {
            /** @var ReusableSourceInstanceAssignment $assignment */
            return $assignment->getId() == $assignmentId;
        })->first();
        if (!$assignment || !$application) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('EDIT', $application);

        if (!$this->isCsrfTokenValid('layerset', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $em = $this->getEntityManager();
        $layerset->getReusableInstanceAssignments()->removeElement($assignment);
        $em->remove($assignment);
        WeightSortedCollectionUtil::reassignWeights($layerset->getCombinedInstanceAssignments());
        $application->setUpdated(new \DateTime('now'));
        $em->persist($application);
        $em->persist($layerset);
        $em->flush();
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
     * @ManagerRoute("/{application}/regionproperties/{regionName}", methods={"POST"})
     */
    public function updateregionpropertiesAction(Request $request, Application $application, $regionName)
    {
        $this->denyAccessUnlessGranted('EDIT', $application);
        // Provided by AbstractController
        /** @see \Symfony\Bundle\FrameworkBundle\Controller\AbstractController::getSubscribedServices() */
        /** @var FormFactoryInterface $factory */
        $factory = $this->get('form.factory');
        $formBuilder = $factory->createNamedBuilder('application', 'Symfony\Component\Form\Extension\Core\Type\FormType', $application);
        $formBuilder->add('regionProperties', 'Mapbender\ManagerBundle\Form\Type\Application\RegionPropertiesType', array(
            'application' => $application,
            'region_names' => array($regionName),
        ));
        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getEntityManager();
            $em->persist($application);
            $application->setUpdated(new \DateTime());
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } else {
            return new JsonResponse(\strval($form->getErrors()), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Create the application form, set extra options needed
     *
     * @param Application $application
     * @return FormInterface
     */
    private function createApplicationForm(Application $application)
    {
        $form = $this->createForm('Mapbender\ManagerBundle\Form\Type\ApplicationType', $application);
        if ($this->allowAclEditing($application)) {
            $aclOptions = array();
            if ($application->getId()) {
                $aclOptions['object_identity'] = ObjectIdentity::fromDomainObject($application);
            } else {
                $aclOptions['data'] = array(
                    array(
                        'sid' => UserSecurityIdentity::fromToken($this->getUserToken()),
                        'mask' => MaskBuilder::MASK_OWNER,
                    ),
                );
            }
            $form->add('acl', 'FOM\UserBundle\Form\Type\ACLType', $aclOptions);
        }
        return $form;
    }

    /**
     * @param Application $application
     * @return bool
     */
    protected function allowAclEditing(Application $application)
    {
        if (!$application->getId()) {
            // current user will become owner of the new application
            return true;
        } elseif ($this->isGranted('OWNER', $application)) {
            return true;
        } else {
            $aclOid = new ObjectIdentity('class', 'Symfony\Component\Security\Acl\Domain\Acl');
            return $this->isGranted('EDIT', $aclOid);
        }
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
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('security.token_storage');
        return $tokenStorage->getToken();
    }
}
