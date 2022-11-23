<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\ExportHandler;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\ManagerBundle\Component\ImportJob;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

class ApplicationExchangeController extends AbstractController
{
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;
    /** @var ImportHandler */
    protected $importHandler;
    /** @var ExportHandler */
    protected $exportHandler;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                ImportHandler $importHandler,
                                ExportHandler $exportHandler)
    {
        $this->yamlRepository = $yamlRepository;
        $this->importHandler = $importHandler;
        $this->exportHandler = $exportHandler;
    }

    /**
     * Imports serialized application.
     *
     * @ManagerRoute("/application/import", name="mapbender_manager_application_import", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function importAction(Request $request)
    {
        $applicationOid = new ObjectIdentity('class', get_class(new Application()));
        $this->denyAccessUnlessGranted('CREATE', $applicationOid);
        $job = new ImportJob();
        $form = $this->createForm('Mapbender\ManagerBundle\Form\Type\ImportJobType', $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $job->getImportFile();
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()->getManager();
            $em->beginTransaction();
            $currentUserSid = UserSecurityIdentity::fromAccount($this->getUser());
            try {
                $data = $this->importHandler->parseImportData($file);
                $applications = $this->importHandler->importApplicationData($data);
                foreach ($applications as $app) {
                    $this->importHandler->addOwner($app, $currentUserSid);
                }
                $em->commit();
                return $this->redirectToRoute('mapbender_manager_application_index');
            } catch (ImportException $e) {
                $em->rollback();
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
    public function copyDirectlyAction($slug)
    {
        /** @var Application|null $sourceApplication */
        $sourceApplication = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        $sourceApplication = $sourceApplication ?: $this->yamlRepository->getApplication($slug);
        if (!$sourceApplication) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted('EDIT', $sourceApplication);
        $applicationOid = new ObjectIdentity('class', get_class(new Application()));
        $this->denyAccessUnlessGranted('CREATE', $applicationOid);

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        $em->beginTransaction();
        try {
            $clonedApp = $this->importHandler->duplicateApplication($sourceApplication);
            $this->importHandler->addOwner($clonedApp, UserSecurityIdentity::fromAccount($this->getUser()));

            $em->commit();
            if ($this->isGranted('EDIT', $clonedApp)) {
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
            $em->rollback();
            $this->addFlash('error', $e->getMessage());
            return $this->forward('MapbenderManagerBundle:Application:index');
        }
    }

    /**
     * Export application as json (direct link)
     * @ManagerRoute("/application/{slug}/export", name="mapbender_manager_application_exportdirect", methods={"GET"})
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function exportdirectAction(Request $request, $slug)
    {
        /** @var Application|null $application */
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if (!$application) {
            throw $this->createNotFoundException("No such application");
        }
        $this->denyAccessUnlessGranted('EDIT', $application);
        $data = $this->exportHandler->exportApplication($application);
        $fileName = "{$application->getSlug()}.json";
        return new JsonResponse($data, 200, array(
            'Content-disposition' => "attachment; filename={$fileName}",
        ));
    }
}
