<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Component\Application as AppComponent;
use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Controller\WelcomeController;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Form\Type\LayersetType;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\ExportHandler;
use Mapbender\ManagerBundle\Component\ExportJob;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\ManagerBundle\Component\ImportJob;
use Mapbender\ManagerBundle\Component\UploadScreenshot;
use Mapbender\ManagerBundle\Form\Type\ApplicationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
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
     * @return Response
     */
    public function indexAction()
    {
        return $this->listAction();
    }

    /**
     * Shows form for creating new applications
     *
     * @ManagerRoute("/application/new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $application = new Application();
        $oid = new ObjectIdentity('class', get_class($application));
        $this->denyAccessUnlessGranted('CREATE', $oid);

        $form = $this->createApplicationForm($application);

        return $this->render('@MapbenderManager/Application/new.html.twig', array(
            'application'         => $application,
            'form'                => $form->createView(),
            'form_name'           => $form->getName(),
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
        $form = $expHandler->createForm();
        if ($request->isMethod(Request::METHOD_POST) && $form->submit($request)->isValid()) {
            /** @var ExportJob $job */
            $job = $form->getData();
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

        $form = $impHandler->createForm();
        if ($request->isMethod(Request::METHOD_POST) && $form->submit($request)->isValid()) {
            /** @var ImportJob $job */
            $job = $form->getData();
            $file = $job->getImportFile();
            /** @var Connection $defaultConnection */
            $defaultConnection = $this->getDoctrine()->getConnection('default');
            $defaultConnection->beginTransaction();
            try {
                $data = $impHandler->parseImportData($file);
                $applications = $impHandler->importApplicationData($data);
                foreach ($applications as $app) {
                    $impHandler->setDefaultAcls($app, $this->getUser());
                }

                $defaultConnection->commit();
                return $this->redirect(
                    $this->generateUrl('mapbender_manager_application_index')
                );
            } catch (ImportException $e) {
                $defaultConnection->rollBack();
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
        $sourceApplication = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('EDIT', $sourceApplication);
        $applicationOid = new ObjectIdentity('class', get_class(new Application()));
        $this->denyAccessUnlessGranted('CREATE', $applicationOid);

        $expHandler = $this->getApplicationExporter();
        $impHandler = $this->getApplicationImporter();
        $data = $expHandler->exportApplication($sourceApplication);
        /** @var Connection $defaultConnection */
        $defaultConnection = $this->getDoctrine()->getConnection('default');
        $defaultConnection->beginTransaction();
        try {
            $impHandler->importApplicationData($data, true);
            $defaultConnection->commit();
            return $this->redirect($this->generateUrl('mapbender_manager_application_index'));
        } catch (ImportException $e) {
            $defaultConnection->rollBack();
            $this->addFlash('error', $e->getMessage());
            return $this->forward('MapbenderManagerBundle:Application:index');
        }
    }

    /**
     * Create a new application from POSTed data
     *
     * @ManagerRoute("/application", methods={"POST"})
     * @param Request $request
     * @return Response|array
     */
    public function createAction(Request $request)
    {
        $application      = new Application();
        $uploadScreenShot = new UploadScreenshot();

        $oid = new ObjectIdentity('class', get_class($application));
        $this->denyAccessUnlessGranted('CREATE', $oid);

        $form          = $this->createApplicationForm($application);
        $form->handleRequest($request);
        $parameters    = $request->request->get('application');

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@MapbenderManager/Application/new.html.twig', array(
                'application'         => $application,
                'form'                => $form->createView(),
                'form_name'           => $form->getName(),
                'screenshot_filename' => null,
            ));
        }

        try {
            $appDirectory = $this->getUploadsManager()->getSubdirectoryPath($application->getSlug(), true);
        } catch (IOException $e) {
            $this->addFlash('error', $this->translate('mb.application.create.failure.create.directory'));
            return $this->redirect($this->generateUrl('mapbender_manager_application_index'));
        }
        $application->setUpdated(new \DateTime('now'));
        $em = $this->getDoctrine()->getManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();
        $connection->beginTransaction();
        $em->persist($application);
        $em->flush();
        $this->checkRegionProperties($application);
        $aclManager = $this->get('fom.acl.manager');
        $aclManager->setObjectACLFromForm($application, $form->get('acl'), 'object');
        $scFile = $application->getScreenshotFile();

        if ($scFile !== null
            && $parameters['removeScreenShot'] !== '1'
            && $parameters['uploadScreenShot'] !== '1'
        ) {
            $uploadScreenShot->upload($appDirectory, $scFile, $application);
        }

        $em->persist($application);
        $em->flush();

        $templateClass = $application->getTemplate();
        $templateProps = $templateClass::getRegionsProperties();

        foreach ($templateProps as $regionName => $regionProps) {
            $application->addRegionProperties(
                $this->createRegionProperties($application, $regionName, $regionProps)
            );
        }

        $em->persist($application);
        $em->flush();
        $aclManager = $this->get('fom.acl.manager');
        $aclManager->setObjectACLFromForm($application, $form->get('acl'), 'object');
        $connection->commit();
        $this->addFlash('success', $this->translate('mb.application.create.success'));

        return $this->redirect($this->generateUrl('mapbender_manager_application_index'));
    }

    /**
     * Edit application
     *
     * @ManagerRoute("/application/{slug}/edit", requirements = { "slug" = "[\w-]+" }, methods={"GET"})
     * @param string $slug Application name
     * @return Response
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function editAction($slug)
    {
        /** @var Application $application */
        $application = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);

        $this->checkRegionProperties($application);
        $form = $this->createApplicationForm($application);

        return $this->render('@MapbenderManager/Application/edit.html.twig',
            $this->prepareApplicationUpdate($form, $application));
    }

    /**
     * Updates application
     *
     * @ManagerRoute("/application/{slug}/update", requirements = { "slug" = "[\w-]+" }, methods={"POST"})
     * @param Request $request
     * @param string $slug
     * @return Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateAction(Request $request, $slug)
    {
        /** @var EntityManager $em */
        /** @var Connection $connection */
        $application      = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);

        $oldSlug          = $application->getSlug();
        $templateClassOld = $application->getTemplate();
        $form             = $this->createApplicationForm($application);

        if (!$form->submit($request)->isValid()) {
            $application->setTemplate($templateClassOld);
            $application->setSlug($slug);
            return $this->render('@MapbenderManager/Application/edit.html.twig',
                $this->prepareApplicationUpdate($form, $application));
        }

        $em         = $this->getDoctrine()->getManager();
        $connection = $em->getConnection();

        $connection->beginTransaction();
        $application->setUpdated(new \DateTime('now'));
        $application->setTemplate($templateClassOld);
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
            $scFile = $application->getScreenshotFile();
            if ($scFile) {
                $fileType = getimagesize($scFile);
                $parameters = $request->request->get('application');
                if ($parameters['removeScreenShot'] !== '1' && $parameters['uploadScreenShot'] !== '1'
                    && strpos($fileType['mime'], 'image') !== false
                ) {
                    $uploadScreenShot = new UploadScreenshot();
                    $uploadScreenShot->upload($uploadPath, $scFile, $application);
                }
            }
            $em->persist($application);
            $em->flush();
            $aclManager = $this->get('fom.acl.manager');
            $aclManager->setObjectACLFromForm($application, $form->get('acl'), 'object');
            $connection->commit();
            $this->addFlash('success', $this->translate('mb.application.save.success'));
        } catch (IOException $e) {
            $this->addFlash('error', $this->translate('mb.application.save.failure.create.directory') . ": {$e->getMessage()}");
            $connection->rollBack();
            $em->close();
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translate('mb.application.save.failure.general'));
            $connection->rollBack();
            $em->close();
        }
        return $this->redirect($this->generateUrl(
            'mapbender_manager_application_edit',
            array('slug' => $application->getSlug())
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
        $application = $this->getMapbender()->getApplicationEntity($slug);

        $this->denyAccessUnlessGranted('EDIT', $application);

        $em = $this->getDoctrine()->getManager();

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
        $application = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('DELETE', $application);

        try {
            $em          = $this->getDoctrine()->getManager();
            $aclProvider = $this->get('security.acl.provider');
            $oid         = ObjectIdentity::fromDomainObject($application);
            $em->getConnection()->beginTransaction();
            $aclProvider->deleteAcl($oid);
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
     * Create a form for a new layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/new", methods={"GET"})
     * @param string $slug
     * @return Response
     */
    public function newLayersetAction($slug)
    {
        $application = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        $layerset    = new Layerset();
        $layerset->setApplication($application);

        $form = $this->createForm(new LayersetType(), $layerset);

        return $this->render('@MapbenderManager/Application/form-layerset.html.twig', array(
            "isnew" => true,
            "application" => $application,
            'form' => $form->createView(),
        ));
    }

    /**
     * Create a new layerset from POSTed data
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/edit", methods={"GET"})
     * @param string $slug
     * @param string $layersetId
     * @return Response
     */
    public function editLayersetAction($slug, $layersetId)
    {
        $application = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        $layerset    = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:Layerset")
            ->find($layersetId);

        $form = $this->createForm(new LayersetType(), $layerset);

        return $this->render('@MapbenderManager/Application/form-layerset.html.twig', array(
            "isnew" => false,
            "application" => $application,
            'form' => $form->createView(),
        ));
    }

    /**
     * Create a new layerset from POSTed data
     *
     * @ManagerRoute("/application/{slug}/layerset/create", methods={"POST"})
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/save", methods={"POST"})
     * @param Request $request
     * @param string $slug
     * @param string|null $layersetId
     * @return Response
     */
    public function saveLayersetAction(Request $request, $slug, $layersetId = null)
    {
        /** @var Application $application */
        $application = $this->getMapbender()->getApplicationEntity($slug);

        $this->denyAccessUnlessGranted('EDIT', $application);
        $isNew = ($layersetId === null);

        $doctrine = $this->getDoctrine();
        if ($isNew) {
            $layerset = new Layerset();
            $layerset->setApplication($application);
        } else {
            $layerset = $doctrine
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId);
        }
        $form = $this->createForm(new LayersetType(), $layerset);
        $form->submit($request);
        if ($form->isValid()) {
            $objectManager = $doctrine->getManager();
            $application->setUpdated(new \DateTime('now'));
            $objectManager->persist($application);
            $objectManager->persist($layerset);
            $objectManager->flush();
            $this->get("logger")->debug("Layerset saved");
            $this->addFlash('success', $this->translate('mb.layerset.create.success'));
        } else {
            $this->addFlash('error', $this->translate('mb.layerset.create.failure.unique.title'));
        }
        return $this->redirect($this->generateUrl('mapbender_manager_application_edit', array('slug' => $slug)));
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
        $application = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        $layerset    = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:Layerset")
            ->find($layersetId);
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
        $application = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);
        $layerset    = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:Layerset")
            ->find($layersetId);
        if ($layerset !== null) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $em->remove($layerset);
            $application->setUpdated(new \DateTime('now'));
            $this->getDoctrine()->getManager()->persist($application);
            $em->flush();
            $em->getConnection()->commit();
            $this->get("logger")->debug('The layerset "' . $layerset->getId() . '"has been deleted.');
            $this->addFlash('success', $this->translate('mb.layerset.remove.success'));
        } else {
            $this->addFlash('error', $this->translate('mb.layerset.remove.failure'));
        }
        return new Response();
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/list", methods={"GET"})
     *
     * @param string  $slug Application slug
     * @param int     $layersetId Layer set ID
     * @return Response
     */
    public function listSourcesAction($slug, $layersetId)
    {
        $application = $this->getMapbender()->getApplicationEntity($slug);

        $this->denyAccessUnlessGranted('EDIT', $application);

        $layerset = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:Layerset")
            ->find($layersetId);

        $em              = $this->getDoctrine()->getManager();
        $query           = $em->createQuery("SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources         = $query->getResult();
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $allowed_sources = array();
        foreach ($sources as $source) {
            if ($this->isGranted('VIEW', $oid)
                || $this->isGranted('VIEW', $source)
            ) {
                $allowed_sources[] = $source;
            }
        }

        return $this->render('@MapbenderManager/Application/list-source.html.twig', array(
            'application' => $application,
            'layerset' => $layerset,
            'sources' => $allowed_sources,
        ));
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/source/{sourceId}/add", methods={"GET"})
     *
     * @param Request $request
     * @param string  $slug Application slug
     * @param int     $layersetId Layer set ID
     * @param int     $sourceId Layer set source ID
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function addInstanceAction(Request $request, $slug, $layersetId, $sourceId)
    {
        /** @var Connection $connection */
        $application     = $this->getMapbender()->getApplicationEntity($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);

        $doctrine      = $this->getDoctrine();
        $entityManager = $doctrine->getManager();
        $connection    = $entityManager->getConnection();
        $container     = $this->container;
        $source        = $entityManager->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        $layerSet      = $entityManager->getRepository("MapbenderCoreBundle:Layerset")->find($layersetId);
        $eHandler      = SourceEntityHandler::createHandler($container, $source);
        $connection->beginTransaction();
        $sourceInstance = $eHandler->createInstance($layerSet);
        $instanceSaveHandler = SourceInstanceEntityHandler::createHandler($container, $sourceInstance);
        $instanceSaveHandler->save();
        $entityManager->flush();
        $connection->commit();
        $this->get("logger")
            ->debug('A new instance "' . $sourceInstance->getId() . '"has been created. Please edit it!');
        $flashBag = $request->getSession()->getFlashBag();
        $flashBag->set('success', $this->translate('mb.source.instance.create.success'));
        return $this->redirect($this->generateUrl(
            "mapbender_manager_repository_instance",
            array("slug" => $slug, "instanceId" => $sourceInstance->getId())
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
        $application = $this->getMapbender()->getApplicationEntity($slug);

        $this->denyAccessUnlessGranted('EDIT', $application);

        $sourceInst = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);

        $managers   = $this->getMapbender()->getRepositoryManagers();
        $manager    = $managers[ $sourceInst->getSource()->getManagertype() ];
        return $this->forward("{$manager['bundle']}:Repository:deleteInstance", array(
            "slug"        => $slug,
            "instanceId"  => $instanceId,
        ));
    }

    /**
     * Create the application form, set extra options needed
     *
     * @param Application $application
     * @return Form
     */
    private function createApplicationForm(Application $application)
    {
        $availableTemplates = array();
        $availableProperties = array();

        foreach ($this->getMapbender()->getTemplates() as $templateClassName) {
            $availableTemplates[$templateClassName] = $templateClassName::getTitle();
        }
        asort($availableTemplates);
        if ($application->getTemplate() !== null) {
            $templateClassName    = $application->getTemplate();
            $availableProperties = $templateClassName::getRegionsProperties();
        }

        return $this->createForm(
            new ApplicationType(),
            $application,
            array(
                'available_templates'  => $availableTemplates,
                'available_properties' => $availableProperties,
                'maxFileSize'          => 2097152,
                'screenshotWidth'      => 200,
                'screenshotHeight'     => 200,
            )
        );
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
                    $regprops = $form->get($regionName)->getData();
                    $regionProperty->setProperties($regprops ? $regionProperties[ $regprops ] : array());
                }
            }
        }
    }

    /**
     * Update application region properties if they'r changed
     *
     * @param $application
     */
    private function checkRegionProperties(Application $application)
    {
        $templateClass = $application->getTemplate();
        $templateProps = $templateClass::getRegionsProperties();
        $em            = $this->getDoctrine()->getManager();
        // add RegionProperties if defined
        foreach ($templateProps as $regionName => $regionProps) {
            $exists = false;
            foreach ($application->getRegionProperties() as $regprops) {
                if ($regprops->getName() === $regionName) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $application->addRegionProperties(
                    $this->createRegionProperties($application, $regionName)
                );
                $em->persist($application);
                $em->flush();
            }
        }
    }

    /**
     * Create application region properties
     *
     * @param Application $application
     * @param             $regionName
     * @param             $initValues
     * @return RegionProperties
     */
    protected function createRegionProperties(Application $application, $regionName, array $initValues = null)
    {
        $em         = $this->getDoctrine()->getManager();
        $properties = new RegionProperties();

        $properties->setApplication($application);
        $properties->setName($regionName);

        if ($initValues) {
            foreach ($initValues as $name => $value) {
                if (array_key_exists('state', $value) && $value['state']) {
                    $properties->addProperty($name);
                }
            }
        }

        $em->persist($properties);
        $em->flush();

        return $properties;
    }

    /**
     * @param Form        $form
     * @param Application $application
     * @return array
     */
    protected function prepareApplicationUpdate(Form $form, $application)
    {
        /** @var Element $element */
        /** @var EntityManager $em */
        $slug          = $application->getSlug();
        $templateClass = $application->getTemplate();
        $screenShot = $application->getScreenshot();
        if ($screenShot) {
            $screenShotUrl = AppComponent::getUploadsUrl($this->container) . "/" . $application->getSlug() . "/" . $application->getScreenshot();
            $screenShotUrl = UrlUtil::validateUrl($screenShotUrl, array(
                't' => date('d.m.Y-H:i:s'),
            ));
        } else {
            $screenShotUrl = null;
        }

        return array(
            'application'         => $application,
            'aclManager'          => $this->get("fom.acl.manager"),
            'regions'             => $templateClass::getRegions(),
            'slug'                => $slug,
            'form'                => $form->createView(),
            'form_name'           => $form->getName(),
            'template_name'       => $templateClass::getTitle(),
            'screenshot'          => $screenShotUrl,
            'screenshot_filename' => $screenShot,
            'time'                => new \DateTime());
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
     * @return UploadsManager
     */
    protected function getUploadsManager()
    {
        /** @var UploadsManager $service */
        $service = $this->get('mapbender.uploads_manager.service');
        return $service;
    }
}
