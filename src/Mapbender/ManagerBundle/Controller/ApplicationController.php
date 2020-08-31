<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\DBALException;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Controller\WelcomeController;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\ExportHandler;
use Mapbender\ManagerBundle\Component\ExportJob;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\ManagerBundle\Component\ImportJob;
use Mapbender\ManagerBundle\Component\UploadScreenshot;
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Mapbender application management
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmitd <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class ApplicationController extends WelcomeController
{
    /**
     * Render a list of applications the current logged in user has access to.
     *
     * @ManagerRoute("/applications", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        return $this->listAction($request);
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
                $appDirectory = $this->getUploadsManager()->getSubdirectoryPath($application->getSlug(), true);
            } catch (IOException $e) {
                $this->addFlash('error', $this->translate('mb.application.create.failure.create.directory'));
                return $this->redirectToRoute('mapbender_manager_application_index');
            }
            $application->setUpdated(new \DateTime('now'));
            $em = $this->getEntityManager();

            $em->beginTransaction();
            $em->persist($application);
            $em->flush();
            if ($form->has('acl')) {
                $this->getAclManager()->setObjectACEs($application, $form->get('acl')->get('ace')->getData());
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
            $this->addFlash('success', $this->translate('mb.application.create.success'));

            return $this->redirectToRoute('mapbender_manager_application_edit', array(
                'slug' => $application->getSlug(),
            ));
        }

        return $this->render('@MapbenderManager/Application/new.html.twig', array(
            'application'         => $application,
            'form'                => $form->createView(),
            'screenshot_filename' => null,
        ));
    }

    /**
     * Returns serialized application.
     *
     * @ManagerRoute("/application/export", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function exportAction(Request $request)
    {
        $expHandler = $this->getApplicationExporter();
        $job = new ExportJob();
        $form = $this->createForm('Mapbender\ManagerBundle\Form\Type\ExportJobType', $job, array(
            'application' => $this->getExportableApplications(),
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $expHandler->exportApplication($job->getApplication());
            switch ($job->getFormat()) {
                case ExportJob::FORMAT_JSON:
                    return new JsonResponse($data, 200, array(
                        'Content-disposition' => 'attachment; filename=export.json',
                    ));
                    break;
                case ExportJob::FORMAT_YAML:
                    $content = Yaml::dump($data, 20);
                    return new Response($content, 200, array(
                        'Content-Type'        => 'text/plain',
                        'Content-disposition' => 'attachment; filename=export.yaml',
                    ));
                    break;
                default:
                    throw new BadRequestHttpException("mb.manager.controller.application.method_not_supported");
            }

        } else {
            return $this->render('@MapbenderManager/Application/export.html.twig', array(
                'form' => $form->createView(),
            ));
        }
    }

    /**
     * Imports serialized application.
     *
     * @ManagerRoute("/application/import", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     * @throws DBALException
     */
    public function importAction(Request $request)
    {
        $applicationOid = new ObjectIdentity('class', get_class(new Application()));
        $this->denyAccessUnlessGranted('CREATE', $applicationOid);
        $impHandler = $this->getApplicationImporter();
        $job = new ImportJob();
        $form = $this->createForm('Mapbender\ManagerBundle\Form\Type\ImportJobType', $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $job->getImportFile();
            $em = $this->getEntityManager();
            $em->beginTransaction();
            $currentUserSid = UserSecurityIdentity::fromToken($this->getUserToken());
            try {
                $data = $impHandler->parseImportData($file);
                $applications = $impHandler->importApplicationData($data);
                foreach ($applications as $app) {
                    $impHandler->addOwner($app, $currentUserSid);
                }
                $em->commit();
                return $this->redirectToRoute('mapbender_manager_application_index');
            } catch (ImportException $e) {
                $em->rollback();
                $message = $this->translate('mb.manager.import.application.failed') . " " . $e->getMessage();
                $this->addFlash('error', $message);
                // fall through to re-rendering form
            }
        }
        return $this->render('@MapbenderManager/Application/import.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Copies an application
     *
     * @ManagerRoute("/application/{slug}/copydirectly", requirements = { "slug" = "[\w-]+" }, methods={"GET"})
     * @param string $slug
     * @return Response
     * @throws DBALException
     */
    public function copyDirectlyAction($slug)
    {
        $sourceApplication = $this->requireApplication($slug, true);
        $this->denyAccessUnlessGranted('EDIT', $sourceApplication);
        $applicationOid = new ObjectIdentity('class', get_class(new Application()));
        $this->denyAccessUnlessGranted('CREATE', $applicationOid);

        $impHandler = $this->getApplicationImporter();
        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $clonedApp = $impHandler->duplicateApplication($sourceApplication);
            $impHandler->addOwner($clonedApp, UserSecurityIdentity::fromToken($this->getUserToken()));

            $em->commit();
            if ($this->isGranted('EDIT', $clonedApp)) {
                // Redirect to edit view of imported application
                // @todo: distinct message for successful duplication?
                $this->addFlash('success', $this->translate('mb.application.create.success'));
                return $this->redirectToRoute('mapbender_manager_application_edit', array(
                    'slug' => $clonedApp->getSlug(),
                ));
            } else {
                return $this->redirectToRoute('mapbender_manager_application_index');
            }
        } catch (ImportException $e) {
            $em->rollback();
            $this->addFlash('error', $e->getMessage());
            return $this->forward('MapbenderManagerBundle:Application:index');
        }
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
        $application = $this->requireApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);

        $oldSlug          = $application->getSlug();

        $form             = $this->createApplicationForm($application);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getEntityManager();
            $em->beginTransaction();
            $application->setUpdated(new \DateTime('now'));
            $this->setRegionProperties($application, $form);
            if ($form->get('removeScreenShot')->getData() == '1') {
                $application->setScreenshot(null);
            }
            $em->persist($application);
            $em->flush();

            try {
                $ulm = $this->getUploadsManager();
                if ($oldSlug !== $application->getSlug()) {
                    $uploadPath = $ulm->renameSubdirectory($oldSlug, $application->getSlug(), true);
                } else {
                    $uploadPath = $ulm->getSubdirectoryPath($application->getSlug(), true);
                }
                $scFile = $form->get('screenshotFile')->getData();
                if ($scFile && !$form->get('removeScreenShot')->getData()) {
                    $uploadScreenShot = new UploadScreenshot();
                    $uploadScreenShot->upload($uploadPath, $scFile, $application);
                }
                $em->persist($application);
                $em->flush();
                if ($form->has('acl')) {
                    $this->getAclManager()->setObjectACEs($application, $form->get('acl')->get('ace')->getData());
                }
                $em->commit();
                $this->addFlash('success', $this->translate('mb.application.save.success'));
                return $this->redirectToRoute('mapbender_manager_application_edit', array(
                    'slug' => $application->getSlug(),
                ));
            } catch (IOException $e) {
                $this->addFlash('error', $this->translate('mb.application.save.failure.create.directory') . ": {$e->getMessage()}");
                $em->rollback();
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translate('mb.application.save.failure.general'));
                $em->rollback();
            }
        }

        $templateClass = $application->getTemplate();
        $screenShot = $application->getScreenshot();
        if ($screenShot) {
            $baseUrl = $this->getUploadsBaseUrl($request);
            $screenShotUrl = $baseUrl ."/{$application->getSlug()}/{$screenShot}";
            $screenShotUrl = UrlUtil::validateUrl($screenShotUrl, array(
                't' => date('d.m.Y-H:i:s'),
            ));
        } else {
            $screenShotUrl = null;
        }

        // restore old slug to keep urls working
        $application->setSlug($oldSlug);
        return $this->render('@MapbenderManager/Application/edit.html.twig', array(
            'application'         => $application,
            'regions'             => $templateClass::getRegions(),
            'form'                => $form->createView(),
            'template_name'       => $templateClass::getTitle(),
            'screenshot'          => $screenShotUrl,
            'screenshot_filename' => $screenShot,
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
        $application = $this->requireApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);

        $em = $this->getEntityManager();

        $requestedState = $request->request->get('state');
        $oldState = $application->isPublished();

        switch ($requestedState) {
            case 'enabled':
            case 'disabled':
                $newState = $requestedState === 'enabled' ? true : false;
                $application->setPublished($newState);
                $em->flush();
                return new JsonResponse(array(
                    'oldState' => ($oldState ? 'enabled' : 'disabled'),
                    'newState' => ($newState ? 'enabled' : 'disabled'),
                ));
            default:
                throw new BadRequestHttpException();
        }
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
        $application = $this->requireApplication($slug);
        $this->denyAccessUnlessGranted('DELETE', $application);

        try {
            $em = $this->getEntityManager();
            $em->beginTransaction();
            $this->getAclProvider()->deleteAcl(ObjectIdentity::fromDomainObject($application));
            $em->remove($application);
            $em->flush();
            $em->commit();
            $this->getUploadsManager()->removeSubdirectory($slug);
            $this->addFlash('success', $this->translate('mb.application.remove.success'));
        } catch (IOException $e) {
            $this->addFlash('error', $this->translate('mb.application.failure.remove.directory'));
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translate('mb.application.remove.failure.general'));
        }

        return new Response();
    }

    /**
     * Handle layerset creation and title editing
     *
     * @ManagerRoute("/application/{slug}/layerset/new", methods={"GET", "POST"}, name="mapbender_manager_application_newlayerset")
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/edit", methods={"GET", "POST"}, name="mapbender_manager_application_editlayerset")
     * @param Request $request
     * @param string $slug
     * @param string|null $layersetId
     * @return Response
     */
    public function editLayersetAction(Request $request, $slug, $layersetId = null)
    {
        $application = $this->requireApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        if ($layersetId) {
            $layerset = $this->requireLayerset($layersetId, $application);
            $action = $this->generateUrl('mapbender_manager_application_editlayerset', array(
                'slug' => $slug,
                'layersetId' => $layerset->getId(),
            ));
        } else {
            $layerset = new Layerset();
            $layerset->setApplication($application);
            $action = $this->generateUrl('mapbender_manager_application_newlayerset', array(
                'slug' => $slug,
            ));
        }

        $form = $this->createForm('Mapbender\CoreBundle\Form\Type\LayersetType', $layerset, array(
            'action' => $action,
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em = $this->getEntityManager();
                $application->setUpdated(new \DateTime('now'));
                $em->persist($application);
                $em->persist($layerset);
                $em->flush();
                $this->addFlash('success', $this->translate('mb.layerset.create.success'));
            } else {
                // @todo: use form error translations directly; also support message for empty title
                $this->addFlash('error', $this->translate('mb.layerset.create.failure.unique.title'));
            }
            // NOTE: Symfony 2.8 router does not support "_fragment" magic parameter
            $redirectUrl = $this->generateUrl('mapbender_manager_application_edit', array(
                'slug' => $slug,
            ));
            return $this->redirect($redirectUrl . '#tabLayers');
        }

        return $this->render('@MapbenderManager/Application/form-layerset.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * A confirmation page for a layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/delete", methods={"GET"})
     * @param string $slug
     * @param string $layersetId
     * @return Response
     */
    public function confirmDeleteLayersetAction($slug, $layersetId)
    {
        $application = $this->requireApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        $layerset = $this->requireLayerset($layersetId, $application);
        return $this->render('@MapbenderManager/Application/deleteLayerset.html.twig', array(
            'layerset' => $layerset,
        ));
    }

    /**
     * Delete a layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/delete", methods={"POST"})
     * @param Request $request
     * @param string $slug
     * @param string $layersetId
     * @return Response
     */
    public function deleteLayersetAction(Request $request, $slug, $layersetId)
    {
        $application = $this->requireApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        $em = $this->getEntityManager();
        try {
            $layerset = $this->requireLayerset($layersetId, $application);
        } catch (NotFoundHttpException $e) {
            /** @todo: remove catch, let 404 fly */
            $layerset = null;
        }
        if ($layerset !== null) {
            $em->beginTransaction();
            $em->remove($layerset);
            $application->setUpdated(new \DateTime('now'));
            $em->persist($application);
            $em->flush();
            $em->commit();
            $this->addFlash('success', $this->translate('mb.layerset.remove.success'));
        } else {
            /** @todo: emit 404 status */
            $this->addFlash('error', $this->translate('mb.layerset.remove.failure'));
        }
        /** @todo: perform redirect server side, not client side */
        return new Response();
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/list", methods={"GET"})
     *
     * @param string $slug of Application
     * @param int $layersetId
     * @return Response
     */
    public function listSourcesAction($slug, $layersetId)
    {
        $application = $this->requireApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        $sourceOid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('VIEW', $sourceOid);

        $layerset = $this->requireLayerset($layersetId, $application);
        $sources = $this->getEntityManager()->getRepository('MapbenderCoreBundle:Source')->findBy(array(), array(
            'title' => 'ASC',
            'id' => 'ASC',
        ));

        return $this->render('@MapbenderManager/Application/list-source.html.twig', array(
            'application' => $application,
            'layerset' => $layerset,
            'sources' => $sources,
        ));
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/source/{sourceId}/add", methods={"GET"})
     *
     * @param Request $request
     * @param string $slug of Application
     * @param int $layersetId
     * @param int $sourceId
     * @return Response
     */
    public function addInstanceAction(Request $request, $slug, $layersetId, $sourceId)
    {
        $entityManager = $this->getEntityManager();
        /** @var Application|null $application */
        $application = $entityManager->getRepository('MapbenderCoreBundle:Application')->findOneBy(array(
            'slug' => $slug,
        ));
        if ($application) {
            $this->denyAccessUnlessGranted('EDIT', $application);
        } else {
            throw $this->createNotFoundException();
        }
        $layerset = $this->requireLayerset($layersetId, $application);
        /** @var Source|null $source */
        $source = $entityManager->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        /** @var TypeDirectoryService $directory */
        $directory = $this->container->get('mapbender.source.typedirectory.service');
        $newInstance = $directory->createInstance($source);
        $otherInstances = $layerset->getInstances()->getValues();
        $newInstance->setWeight(0);
        $newInstance->setLayerset($layerset);
        $layerset->getInstances()->add($newInstance);

        foreach ($otherInstances as $index => $lsInstance) {
            /** @var SourceInstance $lsInstance */
            $lsInstance->setWeight($index + 1);
        }

        $entityManager->persist($application);
        $application->setUpdated(new \DateTime('now'));

        $entityManager->flush();
        $this->addFlash('success', $this->translate('mb.source.instance.create.success'));
        return $this->redirectToRoute("mapbender_manager_repository_instance", array(
            "slug" => $slug,
            "instanceId" => $newInstance->getId(),
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
    public function deleteInstanceAction($slug, $layersetId, $instanceId)
    {
        $em = $this->getEntityManager();
        /** @var Application|null $application */
        $application = $em->getRepository('MapbenderCoreBundle:Application')->findOneBy(array(
            'slug' => $slug,
        ));
        if ($application) {
            $this->denyAccessUnlessGranted('EDIT', $application);
        }
        $layerset = $this->requireLayerset($layersetId, $application);
        $instanceCriteria = new Criteria(Criteria::expr()->eq('id', $instanceId));
        $instance = $layerset->getInstances()->matching($instanceCriteria)->first();
        if (!$instance) {
            throw $this->createNotFoundException();
        }
        $em->persist($application);
        $application->setUpdated(new \DateTime('now'));
        // renumber weights and persist other instances in the same layerset
        WeightSortedCollectionUtil::removeOne($layerset->getInstances(), $instance);
        foreach ($layerset->getInstances() as $remainingInstance) {
            $em->persist($remainingInstance);
        }
        $em->remove($instance);
        $em->flush();
        $this->addFlash('success', 'Your source instance has been deleted');
        return new Response();  // ???
    }

    /**
     * Create the application form, set extra options needed
     *
     * @param Application $application
     * @return Form
     */
    private function createApplicationForm(Application $application)
    {
        return $this->createForm('Mapbender\ManagerBundle\Form\Type\ApplicationType', $application, array(
            'maxFileSize'          => 2097152,
            'screenshotWidth'      => 200,
            'screenshotHeight'     => 200,
            'include_acl' => $this->allowAclEditing($application),
        ));
    }

    /**
     * Merge application, form and template default properties
     *
     * @param Application $application
     * @param Form        $form
     */
    private function setRegionProperties(Application $application, Form $form)
    {
        $templateClass = $application->getTemplate();
        $templateProps = $templateClass::getRegionsProperties();
        $applicationRegionProperties = $application->getRegionProperties();
        foreach ($templateProps as $regionName => $regionProperties) {
            foreach ($applicationRegionProperties as $regionProperty) {
                if ($regionProperty->getName() === $regionName) {
                    $propValues = $regionProperty->getProperties();
                    $formValue = $form->get($regionName)->getData();
                    $propValues = array_replace($propValues, array(
                        'name' => $formValue ?: '',
                    ));
                    // Legacy quirk: label used to be copied into db but is redundant. Only used in form, where
                    // it is taken from the Template, not from the entity.
                    unset($propValues['label']);
                    $regionProperty->setProperties($propValues);

                }
            }
        }
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
        /** @var Template::class $templateClass */
        $templateClass = $application->getTemplate();
        $templateProps = $templateClass::getRegionsProperties();

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
     * Translate string;
     *
     * @param string $key Key name
     * @return string
     */
    protected function translate($key)
    {
        return $this->getTranslator()->trans($key);
    }

    /**
     * @return ImportHandler
     */
    protected function getApplicationImporter()
    {
        /** @var ImportHandler $service */
        $service = $this->get('mapbender.application_importer.service');
        return $service;
    }

    /**
     * @return ExportHandler
     */
    protected function getApplicationExporter()
    {
        /** @var ExportHandler $service */
        $service = $this->get('mapbender.application_exporter.service');
        return $service;
    }

    /**
     * @return Application[]
     */
    protected function getExportableApplications()
    {
        $em = $this->getEntityManager();
        $allowed = array();
        foreach ($em->getRepository('Mapbender\CoreBundle\Entity\Application')->findAll() as $application) {
            if ($this->isGranted('EDIT', $application)) {
                $allowed[] = $application;
            }
        }
        return $allowed;
    }

    /**
     * @return TokenInterface|null
     */
    protected function getUserToken()
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('security.token_storage');
        return $tokenStorage->getToken();
    }
}
