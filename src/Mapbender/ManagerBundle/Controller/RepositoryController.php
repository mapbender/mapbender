<?php

namespace Mapbender\ManagerBundle\Controller;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\Component\Transport\ConnectionErrorException;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Entity\Repository\SourceInstanceRepository;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\Exception\Loader\MalformedXmlException;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\ManagerBundle\Form\Type\HttpSourceOriginType;
use Mapbender\ManagerBundle\Form\Type\HttpSourceSelectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[ManagerRoute("/repository")]
class RepositoryController extends ApplicationControllerBase
{
    public function __construct(
        protected TypeDirectoryService $typeDirectory,
        EntityManagerInterface         $em,
        private TranslatorInterface    $translator,
    )
    {
        parent::__construct($em);
    }

    /**
     * Renders the layer service repository.
     */
    #[ManagerRoute('/', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES);
        $repository = $this->em->getRepository(Source::class);
        /** @var Source[] $sources */
        $sources = $repository->findBy(array(), array(
            'title' => 'ASC',
            'id' => 'ASC',
        ));

        /** @var SourceInstanceRepository $instanceRepository */
        $instanceRepository = $this->em->getRepository(SourceInstance::class);

        $sharedInstances = $instanceRepository->findReusableInstances(array(), array(
            'title' => 'ASC',
            'id' => 'ASC',
        ));

        return $this->render('@MapbenderManager/Repository/index.html.twig', array(
            'sources' => $sources,
            'shared_instances' => $sharedInstances,
            'datasources' => $this->typeDirectory->getTypeLabels(),
            'grants' => array(
                'create' => $this->isGranted(ResourceDomainInstallation::ACTION_CREATE_SOURCES),
                'refresh' => $this->isGranted(ResourceDomainInstallation::ACTION_REFRESH_SOURCES),
                'delete' => $this->isGranted(ResourceDomainInstallation::ACTION_DELETE_SOURCES),
            ),
        ));
    }

    #[ManagerRoute('/new/{sourceType}', methods: ['GET', 'POST'])]
    public function new(Request $request, string $sourceType): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_SOURCES);

        $dataSource = $this->typeDirectory->getSource($sourceType);

        $form = $this->createForm($dataSource->getLoader()->getFormType());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $loader = $this->typeDirectory->getSourceLoaderByType($sourceType);
                $source = $loader->loadSource($form->getData());

                $this->setAliasForDuplicate($source);
                $this->em->beginTransaction();

                $this->em->persist($source);

                $this->em->flush();
                $this->em->commit();

                $message = $this->translator->trans("mb.manager.source.added");
                $replacedMessage = str_replace('%type%', $this->translator->trans($dataSource->getLabel(false)), $message);
                $this->addFlash('success', $replacedMessage);

                return $this->redirectToRoute("mapbender_manager_repository_view", array(
                    "sourceId" => $source->getId(),
                ));
            } catch (ConnectionErrorException $e) {
                $form->addError(new FormError('mb.manager.http_connection_error'));
                $form->addError(new FormError($e->getMessage()));
            } catch (ServerResponseErrorException $e) {
                $form->addError(new FormError('mb.manager.http_error_response'));
                $form->addError(new FormError($e->getMessage()));
            } catch (MalformedXmlException $e) {
                $form->addError(new FormError('mb.manager.xml_malformed'));
                $form->addError(new FormError($e->getContent(true, true, 100) . '...'));
                if ($e->getMessage()) {
                    $form->addError(new FormError($e->getMessage()));
                }
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('@MapbenderManager/Source/add.html.twig', array(
            'form' => $form->createView(),
            'submit_text' => 'mb.manager.source.load',
            'source_label' => $dataSource->getLabel(false),
            'return_path' => 'mapbender_manager_repository_index',
        ));
    }

    /**
     * @param string $sourceId
     * @return Response
     */
    #[ManagerRoute('/source/{sourceId}', methods: ['GET'])]
    public function view($sourceId)
    {
        /** @var Source|null $source */
        $source = $this->em->getRepository(Source::class)->find($sourceId);
        if (!$source) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES);
        $related = $this->getDbApplicationRepository()->findWithInstancesOf($source, null, array(
            'title' => Order::Ascending,
            'id' => Order::Ascending,
        ));
        $grants = \array_filter(array(
            'refresh' => $this->isGranted(ResourceDomainInstallation::ACTION_REFRESH_SOURCES),
            'delete' => $this->isGranted(ResourceDomainInstallation::ACTION_DELETE_SOURCES),
        ));
        $dataSource = $this->typeDirectory->getSource($source->getType());
        return $this->render($dataSource->getMetadataBackendTemplate(), array(
            'source' => $source,
            'applications' => $related,
            'title' => $dataSource->getLabel(true) . ' ' . $source->getTitle(),
            'grants' => $grants,
            // in backend, show all urls
            'secureUrls' => false,
        ));
    }

    /**
     * Deletes a Source (POST) or renders confirmation markup (GET)
     * @param Request $request
     * @param string $sourceId
     * @return Response
     */
    #[ManagerRoute('/source/{sourceId}/delete', methods: ['GET', 'POST', 'DELETE'])]
    public function delete(Request $request, $sourceId)
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES);
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_DELETE_SOURCES);

        $source = $this->em->getRepository(Source::class)->find($sourceId);
        if (!$source) {
            throw $this->createNotFoundException();
        }

        $affectedApplications = $this->getDbApplicationRepository()->findWithInstancesOf($source, null, array(
            'title' => Order::Ascending,
            'id' => Order::Ascending,
        ));

        $dummyForm = $this->createForm(FormType::class, null, array(
            'action' => $this->generateUrl('mapbender_manager_repository_delete', array(
                'sourceId' => $sourceId,
            )),
        ));

        $dummyForm->handleRequest($request);
        if ($request->getMethod() === Request::METHOD_GET) {
            // Use an empty form to help client code follow the final redirect properly
            // See Resources/public/confirm-delete.js
            return $this->render('@MapbenderManager/Repository/confirmdelete.html.twig', array(
                'source' => $source,
                'applications' => $affectedApplications,
                'form' => $dummyForm->createView(),
            ));
        } elseif (!$dummyForm->isSubmitted() || !$dummyForm->isValid()) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirect($this->generateUrl("mapbender_manager_repository_index"));
        }

        // capture permission and entity updates in a single transaction
        $this->em->beginTransaction();

        $dtNow = new \DateTime('now');
        foreach ($affectedApplications as $affectedApplication) {
            $this->em->persist($affectedApplication);
            $affectedApplication->setUpdated($dtNow);
        }

        $this->em->remove($source);
        $this->em->flush();
        $this->em->commit();
        $this->addFlash('success', 'Your source has been deleted');
        return $this->redirect($this->generateUrl("mapbender_manager_repository_index"));
    }

    /**
     * Returns a Source update form.
     *
     * @param Request $request
     * @param string $sourceId
     * @return Response
     */
    #[ManagerRoute('/source/{sourceId}/update', methods: ['GET', 'POST'])]
    public function updateform(Request $request, $sourceId)
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES);
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_REFRESH_SOURCES);

        /** @var Source|null $source */
        $source = $this->em->getRepository(Source::class)->find($sourceId);
        if (!$source) {
            throw $this->createNotFoundException();
        }

        $dataSource = $this->typeDirectory->getSource($source->getType());
        $loader = $dataSource->getLoader();

        $form = $this->createForm($loader->getFormType(), $loader->getRefreshModel($source), ['is_refresh' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->beginTransaction();
            try {
                $loader->refreshSource($source, $form->getData());
                $this->em->persist($source);

                $this->em->flush();
                $this->em->commit();

                $this->addFlash('success', "Your {$source->getType()} source has been updated");
                return $this->redirectToRoute("mapbender_manager_repository_view", array(
                    "sourceId" => $source->getId(),
                ));
            } catch (\Exception $e) {
                $this->em->rollback();
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('@MapbenderManager/Source/reload.html.twig', array(
            'form' => $form->createView(),
            'type_label' => $dataSource->getLabel(false),
            'submit_text' => 'mb.manager.source.load',
            'return_path' => 'mapbender_manager_repository_index',
        ));
    }

    protected function setAliasForDuplicate(Source $source)
    {
        $wmsWithSameTitle = $this->em
            ->getRepository(Source::class)
            ->findBy(array('title' => $source->getTitle()))
        ;

        if (count($wmsWithSameTitle) > 0) {
            $source->setAlias(count($wmsWithSameTitle));
        }
    }

    protected function getDbApplicationRepository(): ApplicationRepository
    {
        return $this->em->getRepository(Application::class);
    }
}
