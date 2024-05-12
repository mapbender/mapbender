<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\ExportHandler;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\ManagerBundle\Component\ImportJob;
use Mapbender\ManagerBundle\Form\Type\ImportJobType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApplicationExchangeController extends AbstractController
{
    public function __construct(protected ApplicationYAMLMapper $yamlRepository,
                                protected ImportHandler         $importHandler,
                                protected ExportHandler         $exportHandler,
                                protected EntityManagerInterface $em)
    {
    }

    /**
     * Imports serialized application.
     *
     * @ManagerRoute("/application/import", name="mapbender_manager_application_import", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function import(Request $request)
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS);
        $job = new ImportJob();
        $form = $this->createForm(ImportJobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $job->getImportFile();
            $this->em->beginTransaction();
            try {
                $data = $this->importHandler->parseImportData($file);
                $applications = $this->importHandler->importApplicationData($data);
                foreach ($applications as $app) {
                    $this->importHandler->addOwner($app, $this->getUser());
                }
                $this->em->commit();
                return $this->redirectToRoute('mapbender_manager_application_index');
            } catch (ImportException $e) {
                $this->em->rollback();
                $this->addFlash('error', 'mb.manager.import.application.failed');
                $this->addFlash('error', ': ' . $e->getMessage());
                // fall through to re-rendering form
            }
        }
        return $this->render('@MapbenderManager/Exchange/import.html.twig', array(
            'form' => $form->createView(),
            'submit_text' => 'mb.manager.admin.application.import.btn.import',
            'return_path' => 'mapbender_manager_application_index',
        ));
    }

    /**
     * Copies an application
     *
     * @ManagerRoute("/application/{slug}/copydirectly", name="mapbender_manager_application_copydirectly", requirements = { "slug" = "[\w-]+" }, methods={"GET"})
     * @param string $slug
     * @return Response
     */
    public function copyDirectly($slug)
    {
        /** @var Application|null $sourceApplication */
        $sourceApplication = $this->em->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        $sourceApplication = $sourceApplication ?: $this->yamlRepository->getApplication($slug);
        if (!$sourceApplication) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $sourceApplication);
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS);

        $this->em->beginTransaction();
        try {
            $clonedApp = $this->importHandler->duplicateApplication($sourceApplication);
            $this->importHandler->addOwner($clonedApp, $this->getUser());

            $this->em->commit();
            if ($this->isGranted(ResourceDomainApplication::ACTION_EDIT, $clonedApp)) {
                // Redirect to edit view of imported application
                // @todo: distinct message for successful duplication?
                $this->addFlash('success', 'mb.application.create.success');
                return $this->redirectToRoute('mapbender_manager_application_edit', array(
                    'slug' => $clonedApp->getSlug(),
                ));
            } else {
                return $this->redirectToRoute('mapbender_manager_application_index');
            }
        } catch (ImportException $e) {
            $this->em->rollback();
            $this->addFlash('error', $e->getMessage());
            return $this->forward('mapbender_manager_application_index');
        }
    }

    /**
     * Export application as json (direct link)
     * @ManagerRoute("/application/{slug}/export", name="mapbender_manager_application_exportdirect", methods={"GET"})
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function exportdirect($slug)
    {
        /** @var Application|null $application */
        $application = $this->em->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if (!$application) {
            throw $this->createNotFoundException("No such application");
        }
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        $data = $this->exportHandler->exportApplication($application);
        $fileName = "{$application->getSlug()}.json";
        return new JsonResponse($data, Response::HTTP_OK, array(
            'Content-disposition' => "attachment; filename={$fileName}",
        ));
    }
}
