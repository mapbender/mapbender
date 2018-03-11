<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Component\Application as AppComponent;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Controller\WelcomeController;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Form\Type\LayersetType;
use Mapbender\ManagerBundle\Component\ExportHandler;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\ManagerBundle\Component\UploadScreenshot;
use Mapbender\ManagerBundle\Form\Type\ApplicationCopyType;
use Mapbender\ManagerBundle\Form\Type\ApplicationType;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @ManagerRoute("/applications")
     * @Method("GET")
     * @Template("MapbenderCoreBundle:Welcome:list.html.twig")
     */
    public function indexAction()
    {
        return $this->listAction();
    }

    /**
     * Shows form for creating new applications
     *
     * @ManagerRoute("/application/new")
     * @Method("GET")
     * @Template
     */
    public function newAction()
    {
        $application = new Application();

        if (!$this->getContext()->isUserAllowedToCreate($application)) {
            throw new AccessDeniedException();
        }

        $form = $this->createApplicationForm($application);

        return array(
            'application'         => $application,
            'form'                => $form->createView(),
            'form_name'           => $form->getName(),
            'screenshot_filename' => null);
    }

    /**
     * Shows a form for exporting applications.
     *
     * @ManagerRoute("/application/export")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:export.html.twig")
     */
    public function exportFormAction()
    {
        $expHandler = new ExportHandler($this->container);
        return array(
            'form' => $expHandler
                ->createForm()
                ->createView()
        );
    }

    /**
     * Returns serialized application.
     *
     * @ManagerRoute("/application/export")
     * @Method("POST")
     * @Template()
     */
    public function exportAction()
    {
        $expHandler = new ExportHandler($this->container);

        if (!$expHandler->bindForm()) {
            return $this->exportFormAction();
        }

        $job     = $expHandler->getJob();
        $headers = null;

        if ($job->isFormatAnJson()) {
            $headers = array(
                'Content-Type'        => 'application/json',
                'Content-disposition' => 'attachment; filename=export.json'
            );
        } elseif ($job->isFormatAnYaml()) {
            $headers = array(
                'Content-Type'        => 'text/plain',
                'Content-disposition' => 'attachment; filename=export.yaml'
            );
        } else {
            throw new AccessDeniedException("mb.manager.controller.application.method_not_supported");
        }

        return new Response(
            $expHandler->format($expHandler->makeJob()),
            200,
            $headers);
    }

    /**
     * Shows a form for importing applications.
     *
     * @ManagerRoute("/application/import")
     * @Template("MapbenderManagerBundle:Application:import.html.twig")
     * @Method("GET")
     */
    public function importFormAction()
    {
        $impHandler = new ImportHandler($this->container, false);
        return array(
            'form' => $impHandler
                ->createForm()
                ->createView()
        );
    }

    /**
     * Imports serialized application.
     *
     * @ManagerRoute("/application/import")
     * @Template
     * @Method("POST")
     */
    public function importAction()
    {
        $impHandler = new ImportHandler($this->container, false);

        if (!$impHandler->bindForm()) {
            return $this->importFormAction();
        }

        $impHandler->makeJob();

        return $this->redirect(
            $this->generateUrl('mapbender_manager_application_index')
        );
    }

    /**
     * Copies an application
     *
     * @ManagerRoute("/application/{slug}/copydirectly", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:form-basic.html.twig")
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Mapbender\ManagerBundle\Component\Exception\ImportException
     */
    public function copyDirectlyAction($slug)
    {
        $sourceApplication = $this->get('mapbender')->getApplicationEntity($slug);

        if (!$this->getContext()->isUserAllowedToEdit($sourceApplication)) {
            throw new AccessDeniedException();
        }

        $expHandler = new ExportHandler($this->container);
        $impHandler = new ImportHandler($this->container, true);
        $expJob     = $expHandler->getJob()
            ->setApplication($sourceApplication)
            ->setAddSources(false);
        $data       = $expHandler->makeJob();

        $importJob  = $impHandler->getJob();
        $importJob->setImportContent($data);
        $impHandler->makeJob();
        return $this->redirect($this->generateUrl('mapbender_manager_application_index'));
    }

    /**
     * Create a new application from POSTed data
     *
     * @ManagerRoute("/application")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Application:new.html.twig")
     */
    public function createAction()
    {
        $application      = new Application();
        $uploadScreenShot = new UploadScreenshot();

        if (!$this->getContext()->isUserAllowedToCreate($application)) {
            throw new AccessDeniedException();
        }

        $form          = $this->createApplicationForm($application);
        $request       = $this->getRequest();
        $parameters    = $request->request->get('application');
        $screenShotUrl = null;

        if (!$form->submit($parameters)->isValid()) {
            return array(
                'application'         => $application,
                'form'                => $form->createView(),
                'form_name'           => $form->getName(),
                'screenshot_filename' => $screenShotUrl);
        }

        $app_directory = AppComponent::getAppWebDir($this->container, $application->getSlug());
        $app_web_url   = AppComponent::getAppWebUrl($this->container, $application->getSlug());
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
            $uploadScreenShot->upload($app_directory, $scFile, $application);
            $app_web_url   = AppComponent::getAppWebUrl($this->container, $application->getSlug());
            $screenShotUrl = $app_web_url . "/" . $application->getScreenshot();
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
        $flashBag = $this->get('session')->getFlashBag();
        if (AppComponent::createAppWebDir($this->container, $application->getSlug())) {
            $flashBag->set('success', $this->translate('mb.application.create.success'));
        } else {
            $connection->rollBack();
            $flashBag->set('error', $this->translate('mb.application.create.failure.create.directory'));
        }
        return $this->redirect($this->generateUrl('mapbender_manager_application_index'));

    }

    /**
     * Edit application
     *
     * @ManagerRoute("/application/{slug}/edit", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template
     * @param string $slug Application name
     * @return array
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function editAction($slug)
    {
        /** @var Application $application */
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        if (!$this->getContext()->isUserAllowedToEdit($application)) {
            throw new AccessDeniedException();
        }

        $this->checkRegionProperties($application);
        $form = $this->createApplicationForm($application);

        return $this->prepareApplicationUpdate($form, $application);
    }

    /**
     * Updates application
     *
     * @ManagerRoute("/application/{slug}/update", requirements = { "slug" = "[\w-]+" })
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Application:edit.html.twig")
     */
    public function updateAction($slug)
    {
        /** @var EntityManager $em */
        /** @var Connection $connection */
        $application      = $this->get('mapbender')->getApplicationEntity($slug);

        if (!$this->getContext()->isUserAllowedToEdit($application)) {
            throw new AccessDeniedException();
        }

        $oldSlug          = $application->getSlug();
        $templateClassOld = $application->getTemplate();
        $form             = $this->createApplicationForm($application);
        $request          = $this->getRequest();

        if (!$form->submit($request)->isValid()) {
            $application->setTemplate($templateClassOld);
            $application->setSlug($slug);
            return $this->prepareApplicationUpdate($form, $application);
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

        $flashBug  = $this->get('session')->getFlashBag();
        $container = $this->container;
        try {
            if (AppComponent::createAppWebDir($container, $application->getSlug(), $oldSlug)) {
                $uploadPath = AppComponent::getAppWebDir($container, $application->getSlug());
                $scFile     = $application->getScreenshotFile();
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
                $flashBug->set('success', $this->translate('mb.application.save.success'));
            } else {
                $flashBug->set('error', $this->translate('mb.application.save.failure.create.directory'));
                $connection->rollback();
                $em->close();
            }
        } catch (\Exception $e) {
            $flashBug->set('error', $this->translate('mb.application.save.failure.general'));
            $connection->rollback();
            $em->close();

            if ($container->getParameter('kernel.debug')) {
                throw($e);
            }
        }
        return $this->redirect($this->generateUrl(
            'mapbender_manager_application_edit',
            array('slug' => $application->getSlug())
        ));
    }

    /**
     * Creates an application form to copy
     *
     * @ManagerRoute("/application/{slug}/copyform", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:form-basic.html.twig")
     */
    public function copyformAction($slug)
    {
        throw new \Exception('check the action copyform');
        $tocopy = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted(SecurityContext::PERMISSION_CREATE, $tocopy);

        $form = $this->createForm(new ApplicationCopyType(), $tocopy);

        return array('form' => $form->createView());
    }

    /**
     * Toggle application state.
     *
     * @ManagerRoute("/application/{slug}/state", options={"expose"=true})
     * @Method("POST")
     */
    public function toggleStateAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        // ACL access check
        $this->checkGranted(SecurityContext::PERMISSION_EDIT, $application);

        $em = $this->getDoctrine()->getManager();

        $requestedState = $this->get('request')->get('state');
        $currentState   = $application->isPublished();
        $newState       = $currentState;

        switch ($requestedState) {
            case 'enabled':
            case 'disabled':
                $newState = $requestedState === 'enabled' ? true : false;
                $application->setPublished($newState);
                $em->flush();
                $message  = 'State switched';
                break;
            case null:
                $message  = 'No state given';
                break;
            default:
                $message  = 'Unknown state requested';
                break;
        }

        return new Response(
            json_encode(
                array(
                'oldState' => $currentState ? 'enabled' : 'disabled',
                'newState' => $newState ? 'enabled' : 'disabled',
                'message' => $message)
            ),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * Delete confirmation page
     * @ManagerRoute("/application/{slug}/delete", requirements = { "slug" = "[\w-]+" })
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:delete.html.twig")
     * @param $slug
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function confirmDeleteAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        if ($application === null) {
            $flashBag = $this->get('session')->getFlashBag();
            $flashBag->set('error', $this->translate('mb.application.remove.failure.already.removed'));
            return $this->redirect($this->generateUrl('mapbender_manager_application_index'));
        }

        if (!$this->getContext()->isUserAllowedToEdit($application)) {
            throw new AccessDeniedException();
        }

        $id = $application->getId();
        return array(
            'application' => $application,
            'form'        => $this->createDeleteForm($id)->createView());
    }

    /**
     * Delete application
     *
     * @ManagerRoute("/application/{slug}/delete", requirements = { "slug" = "[\w-]+" })
     * @Method("POST")
     */
    public function deleteAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        if(!$this->getContext()->isUserAllowedToDelete($application)){
            throw new AccessDeniedException();
        }

        $flashBag = $this->get('session')->getFlashBag();

        try {
            $em          = $this->getDoctrine()->getManager();
            $aclProvider = $this->get('security.acl.provider');
            $oid         = ObjectIdentity::fromDomainObject($application);
            $em->getConnection()->beginTransaction();
            $aclProvider->deleteAcl($oid);
            $em->remove($application);
            $em->flush();
            $em->commit();
            if (AppComponent::removeAppWebDir($this->container, $slug)) {
                $flashBag->set('success', $this->translate('mb.application.remove.success'));
            } else {
                $flashBag->set(
                    'error',
                    $this->translate('mb.application.failure.remove.directory')
                );
            }
        } catch (Exception $e) {
            $flashBag->set('error', $this->translate('mb.application.remove.failure.general'));
        }

        return new Response();
    }

    /**
     * Create a form for a new layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/new")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:form-layerset.html.twig")
     */
    public function newLayersetAction($slug)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted(SecurityContext::PERMISSION_EDIT, $application);
        $layerset    = new Layerset();
        $layerset->setApplication($application);

        $form = $this->createForm(new LayersetType(), $layerset);

        return array(
            "isnew" => true,
            "application" => $application,
            'form' => $form->createView());
    }

    /**
     * Create a new layerset from POSTed data
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/edit")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:form-layerset.html.twig")
     */
    public function editLayersetAction($slug, $layersetId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        // ACL access check
        $this->checkGranted(SecurityContext::PERMISSION_EDIT, $application);
        $layerset    = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:Layerset")
            ->find($layersetId);

        $form = $this->createForm(new LayersetType(), $layerset);

        return array(
            "isnew" => false,
            "application" => $application,
            'form' => $form->createView());
    }

    /**
     * Create a new layerset from POSTed data
     *
     * @ManagerRoute("/application/{slug}/layerset/create")
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/save")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Application:form-layerset.html.twig")
     */
    public function saveLayersetAction($slug, $layersetId = null)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);


        if (!$this->getContext()->isUserAllowedToEdit($application)) {
            throw new AccessDeniedException();
        };

        $doctrine = $this->getDoctrine();
        if ($layersetId === null) { // new object
            $layerset = new Layerset();
            $form     = $this->createForm(new LayersetType(), $layerset);
            $layerset->setApplication($application);
        } else {
            $layerset = $doctrine
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId);
            $form     = $this->createForm(new LayersetType(), $layerset);
        }
        $form->submit($this->get('request'));
        $flashBag = $this->get('session')->getFlashBag();
        if ($form->isValid()) {
            $objectManager = $doctrine->getManager();
            $objectManager->persist($application->setUpdated(new \DateTime('now')));
            $objectManager->persist($layerset);
            $objectManager->flush();
            $this->get("logger")->debug("Layerset saved");
            $flashBag->set('success', $this->translate('mb.layerset.create.success'));
            return $this->redirect($this->generateUrl('mapbender_manager_application_edit', array('slug' => $slug)));
        }
        $flashBag->set('error', $this->translate('mb.layerset.create.failure.unique.title'));
        return $this->redirect($this->generateUrl('mapbender_manager_application_edit', array('slug' => $slug)));
    }

    /**
     * A confirmation page for a layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/confirmdelete")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:deleteLayerset.html.twig")
     */
    public function confirmDeleteLayersetAction($slug, $layersetId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $this->checkGranted(SecurityContext::PERMISSION_EDIT, $application);
        $layerset    = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:Layerset")
            ->find($layersetId);
        return array(
            'application' => $application,
            'layerset' => $layerset,
            'form' => $this->createDeleteForm($layerset->getId())->createView()
        );
    }

    /**
     * Delete a layerset
     *
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/delete")
     * @Method("POST")
     */
    public function deleteLayersetAction($slug, $layersetId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);
        $this->checkGranted(SecurityContext::PERMISSION_EDIT, $application);
        $layerset    = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:Layerset")
            ->find($layersetId);
        $flashBag    = $this->get('session')->getFlashBag();
        if ($layerset !== null) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $em->remove($layerset);
            $this->getDoctrine()->getManager()->persist($application->setUpdated(new \DateTime('now')));
            $em->flush();
            $em->getConnection()->commit();
            $this->get("logger")->debug('The layerset "' . $layerset->getId() . '"has been deleted.');
            $flashBag->set('success', $this->translate('mb.layerset.remove.success'));
        } else {
            $flashBag->set('error',  $this->translate('mb.layerset.remove.failure'));
        }
        return $this->redirect($this->generateUrl('mapbender_manager_application_edit', array('slug' => $slug)));
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/list")
     * @Method("GET")
     * @Template("MapbenderManagerBundle:Application:list-source.html.twig")
     *
     * @param string  $slug Application slug
     * @param int     $layersetId Layer set ID
     * @param Request $request
     * @return array
     */
    public function listSourcesAction($slug, $layersetId, Request $request)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        if (!$this->getContext()->isUserAllowedToEdit($application)) {
            throw new AccessDeniedException();
        }

        $layerset = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:Layerset")
            ->find($layersetId);

        $securityContext = $this->get('security.context');
        $em              = $this->getDoctrine()->getManager();
        $query           = $em->createQuery("SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources         = $query->getResult();
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $allowed_sources = array();
        foreach ($sources as $source) {
            if ($securityContext->isGranted(SecurityContext::PERMISSION_VIEW, $oid)
                || $securityContext->isGranted(SecurityContext::PERMISSION_VIEW, $source)
            ) {
                $allowed_sources[] = $source;
            }
        }

        return array(
            'application' => $application,
            'layerset' => $layerset,
            'sources' => $allowed_sources);
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/source/{sourceId}/add")
     * @Method("GET")
     *
     * @param string  $slug Application slug
     * @param int     $layersetId Layer set ID
     * @param int     $sourceId Layer set source ID
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function addInstanceAction($slug, $layersetId, $sourceId, Request $request)
    {
        /** @var Connection $connection */
        /** @var SecurityContext $securityContext */
        $application     = $this->get('mapbender')->getApplicationEntity($slug);
        $securityContext = $this->get("security.context");

        if (!$securityContext->isUserAllowedToEdit($application)) {
            throw new AccessDeniedException();
        };

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
        $flashBag = $this->get('session')->getFlashBag();
        $flashBag->set('success', $this->translate('mb.source.instance.create.success'));
        return $this->redirect($this->generateUrl(
            "mapbender_manager_repository_instance",
            array("slug" => $slug, "instanceId" => $sourceInstance->getId())
        ));
    }

    /**
     * Delete a source instance from a layerset
     * @ManagerRoute("/application/{slug}/layerset/{layersetId}/instance/{instanceId}/delete")
     * @Method("POST")
     *
     * @param sting $slug
     * @param int   $layersetId
     * @param int   $instanceId
     * @return Response
     * @throws \Exception
     */
    public function deleteInstanceAction($slug, $layersetId, $instanceId)
    {
        $application = $this->get('mapbender')->getApplicationEntity($slug);

        if (!$this->getContext()->isUserAllowedToEdit($application)) {
            throw new AccessDeniedException();
        };

        $sourceInst = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);

        $managers   = $this->get('mapbender')->getRepositoryManagers();
        $manager    = $managers[ $sourceInst->getSource()->getManagertype() ];
        $path       = array(
            '_controller' => $manager['bundle'] . ":" . "Repository:deleteInstance",
            "slug"        => $slug,
            "instanceId"  => $instanceId
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
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

        foreach ($this->get('mapbender')->getTemplates() as $templateClassName) {
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
                'screenshotHeight'     => 200
            )
        );
    }

    /**
     * Collect available elements
     */
    private function getElementList()
    {
        $available_elements = array();
        foreach ($this->get('mapbender')->getElements() as $elementClassName) {
            $available_elements[$elementClassName] = array(
                'title' => $elementClassName::getClassTitle(),
                'description' => $elementClassName::getClassDescription(),
                'tags' => $elementClassName::getClassTags());
        }
        asort($available_elements);

        return $available_elements;
    }

    /**
     * Creates the form for the delete confirmation page
     *
     * @param $id
     * @return Form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
                ->add('id', 'hidden')
                ->getForm();
    }

    /**
     * Checks the grant for an action and an object
     *
     * @param string $action action "CREATE"
     * @param object $object the object
     * @throws AccessDeniedException
     * @deprecated
     */
    private function checkGranted($action, $object)
    {
        if (!$this->getContext()->checkGranted($action, $object, false)) {
            throw new AccessDeniedException();
        }
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
        $em            = $this->getDoctrine()->getManager();
        $query         = $em->createQuery("SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources       = $query->getResult();
        $baseUrl       = AppComponent::getAppWebUrl($this->container, $application->getSlug());
        $screenShotUrl = AppComponent::getUploadsUrl($this->container) . "/" . $application->getSlug() . "/" . $application->getScreenshot();

        if (!$screenShotUrl) {
            $screenShotUrl = $baseUrl . "/" . $application->getScreenshot();
        }

        return array(
            'application'         => $application,
            'aclManager'          => $this->get("fom.acl.manager"),
            'regions'             => $templateClass::getRegions(),
            'slug'                => $slug,
            'available_elements'  => $this->getElementList(),
            'sources'             => $sources,
            'form'                => $form->createView(),
            'form_name'           => $form->getName(),
            'template_name'       => $templateClass::getTitle(),
            'screenshot'          => $screenShotUrl,
            'screenshot_filename' => $application->getScreenshot(),
            'time'                => new \DateTime());
    }
}
