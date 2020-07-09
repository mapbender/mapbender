<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Mapbender\Component\Loader\RefreshableSourceLoader;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\ReusableSourceInstanceAssignment;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceAssignment;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 *  Mapbender repository controller
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 * @ManagerRoute("/repository")
 */
class RepositoryController extends ApplicationControllerBase
{
    /**
     * Renders the layer service repository.
     *
     * @ManagerRoute("/", methods={"GET"})
     * @return Response
     */
    public function indexAction()
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('VIEW', $oid);
        $repository = $this->getDoctrine()->getRepository('Mapbender\CoreBundle\Entity\Source');
        /** @var Source[] $sources */
        $sources = $repository->findBy(array(), array(
            'title' => 'ASC',
            'id' => 'ASC',
        ));

        $reloadableIds = array();
        // NOTE: direct object grants checks do not work because Symfony ACL cannot currently infer from e.g. concrete
        // WmsSource to global grants assigned on abstract base class Source
        // THE ONLY directly assigned grant on a concrete Source is 'OWNER' on newly loaded sources, assigned to the
        // User that added the source to the system, but not editable in any way.
        // => On listings ALWAYS check grants on oids for sources, nothing else works as expected
        if ($this->isGranted('EDIT', $oid)) {
            $typeDirectory = $this->getTypeDirectory();
            foreach ($sources as $source) {
                if ($typeDirectory->getRefreshSupport($source)) {
                    $reloadableIds[] = $source->getId();
                }
            }
        }

        return $this->render('@MapbenderManager/Repository/index.html.twig', array(
            'title' => 'Repository',
            'sources' => $sources,
            'reloadableIds' => $reloadableIds,
            'oid' => $oid,
            'create_permission' => $this->isGranted('CREATE', $oid),
        ));
    }

    /**
     * @ManagerRoute("/new", methods={"GET"})
     * @ManagerRoute("/new/{sourceType}", methods={"POST"}, name="mapbender_manager_repository_new_submit")
     * @param Request $request
     * @param string|null $sourceType
     * @return Response
     */
    public function newAction(Request $request, $sourceType = null)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('CREATE', $oid);

        $sourceTypeLabels = $this->getTypeDirectory()->getTypeLabels();
        /** @var FormInterface[] $forms */
        $forms = array();
        foreach ($sourceTypeLabels as $type => $sourceTypeLabel) {
            $formAction = $this->generateUrl('mapbender_manager_repository_new_submit', array('sourceType' => $type), UrlGeneratorInterface::RELATIVE_PATH);
            $form = $this->createForm('Mapbender\ManagerBundle\Form\Type\HttpSourceOriginType', new HttpOriginModel(), array(
                'action' => $formAction,
            ));
            $forms[$type] = $form;
        }

        if ($sourceType) {
            if (!array_key_exists($sourceType, $forms)) {
                throw new BadRequestHttpException();
            }
            $form = $forms[$sourceType];
            $form->handleRequest($request);
        } else {
            $form = null;
        }
        if ($form && $form->isSubmitted() && $form->isValid()) {
            $directory = $this->getTypeDirectory();
            try {
                $loader = $directory->getSourceLoaderByType($sourceType);
                $source = $loader->evaluateServer($form->getData());

                $this->setAliasForDuplicate($source);
                $em = $this->getEntityManager();
                $em->beginTransaction();

                $em->persist($source);

                $em->flush();
                $this->initializeAccessControl($source);
                $em->commit();
                // @todo: provide translations
                $this->addFlash('success', "A new {$source->getType()} source has been created");
                return $this->redirectToRoute("mapbender_manager_repository_view", array(
                    "sourceId" => $source->getId(),
                ));
            } catch (\Exception $e) {
                $importerResponse = null;
                $form->addError(new FormError($this->getTranslator()->trans($e->getMessage())));
            }
        }

        $formViews = array();
        foreach ($forms as $type => $form) {
            if (!$sourceType) {
                $sourceType = $type;
            }
            $formViews[$type] = $form->createView();
        }

        return $this->render('@MapbenderManager/Repository/new.html.twig', array(
            'sourceTypes' => $sourceTypeLabels,
            'forms' => $formViews,
            'activetype' => $sourceType,
        ));
    }

    /**
     * @ManagerRoute("/source/{sourceId}", methods={"GET"})
     * @param string $sourceId
     * @return Response
     */
    public function viewAction($sourceId)
    {
        $em = $this->getEntityManager();
        /** @var Source|null $source */
        $source = $em->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        if (!$source) {
            throw $this->createNotFoundException();
        }

        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$this->isGranted('VIEW', $oid)) {
            $this->denyAccessUnlessGranted('VIEW', $source);
        }
        $related = $this->getDbApplicationRepository()->findWithInstancesOf($source, null, array(
            'title' => Criteria::ASC,
            'id' => Criteria::ASC,
        ));
        return $this->render($source->getViewTemplate(), array(
            'source' => $source,
            'applications' => $related,
            'title' => $source->getType() . ' ' . $source->getTitle(),
            'wms' => $source,   // HACK: source name in legacy templates
            'wmts' => $source,  // HACK: source name in legacy templates
        ));
    }

    /**
     * Deletes a Source (POST) or renders confirmation markup (GET)
     * @ManagerRoute("/source/{sourceId}/delete", methods={"GET", "POST"})
     * @param Request $request
     * @param string $sourceId
     * @return Response
     */
    public function deleteAction(Request $request, $sourceId)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $em = $this->getEntityManager();
        /** @var Source $source */
        $source = $em->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        if (!$source) {
            // If delete action is forbidden, hide the fact that the source doesn't
            // exist behind an access denied.
            $this->denyAccessUnlessGranted('VIEW', $oid);
            $this->denyAccessUnlessGranted('DELETE', $oid);
            throw $this->createNotFoundException();
        }
        // Must have VIEW + DELETE on either any Source globally, or on this particular
        // Source
        if (!($this->isGranted('VIEW', $oid))) {
            $this->denyAccessUnlessGranted('VIEW', $source);
        }
        if (!($this->isGranted('DELETE', $oid))) {
            $this->denyAccessUnlessGranted('DELETE', $source);
        }
        $affectedApplications = $this->getDbApplicationRepository()->findWithInstancesOf($source, null, array(
            'title' => Criteria::ASC,
            'id' => Criteria::ASC,
        ));
        if ($request->getMethod() === Request::METHOD_GET) {
            return $this->render('@MapbenderManager/Repository/confirmdelete.html.twig',  array(
                'source' => $source,
                'applications' => $affectedApplications,
            ));
        }
        // capture ACL and entity updates in a single transaction
        $em->beginTransaction();
        /** @var MutableAclProviderInterface $aclProvider */
        $aclProvider = $this->get('security.acl.provider');
        $oid         = ObjectIdentity::fromDomainObject($source);
        $aclProvider->deleteAcl($oid);

        $dtNow = new \DateTime('now');
        foreach ($affectedApplications as $affectedApplication) {
            $em->persist($affectedApplication);
            $affectedApplication->setUpdated($dtNow);
        }

        $em->remove($source);
        $em->flush();
        $em->commit();
        $this->addFlash('success', 'Your source has been deleted');
        return $this->redirect($this->generateUrl("mapbender_manager_repository_index"));
    }

    /**
     * Returns a Source update form.
     *
     * @ManagerRoute("/source/{sourceId}/update", methods={"GET", "POST"})
     * @param Request $request
     * @param string $sourceId
     * @return Response
     */
    public function updateformAction(Request $request, $sourceId)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        /** @var Source|null $source */
        $source = $this->getDoctrine()->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        if (!$source) {
            // If edit action is forbidden, hide the fact that the source doesn't
            // exist behind an access denied.
            $this->denyAccessUnlessGranted('VIEW', $oid);
            $this->denyAccessUnlessGranted('EDIT', $oid);
            throw $this->createNotFoundException();
        }
        $canUpdate = $this->getTypeDirectory()->getRefreshSupport($source);
        if (!$canUpdate) {
            throw $this->createNotFoundException();
        }
        // Must have VIEW + EDIT on either any Source globally, or on this particular
        // Source
        if (!$this->isGranted('VIEW', $oid)) {
            $this->denyAccessUnlessGranted('VIEW', $source);
        }
        if (!$this->isGranted('EDIT', $oid)) {
            $this->denyAccessUnlessGranted('EDIT', $source);
        }

        /** @var RefreshableSourceLoader $loader */
        $loader = $this->getTypeDirectory()->getSourceLoaderByType($source->getType());
        $formModel = HttpOriginModel::extract($source);
        $formModel->setOriginUrl($loader->getRefreshUrl($source));
        $form = $this->createForm('Mapbender\ManagerBundle\Form\Type\HttpSourceOriginType', $formModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getEntityManager();
            $em->beginTransaction();
            try {
                $loader->refresh($source, $formModel);
                $em->persist($source);

                $em->flush();
                $em->commit();

                $this->addFlash('success', "Your {$source->getType()} source has been updated");
                return $this->redirectToRoute("mapbender_manager_repository_view", array(
                    "sourceId" => $source->getId(),
                ));
            } catch (\Exception $e) {
                $em->rollback();
                $form->addError(new FormError($this->getTranslator()->trans($e->getMessage())));
            }
        }

        return $this->render('@MapbenderManager/Repository/updateform.html.twig', array(
            'form' => $form->createView(),
            'sourceTypeLabel' => $source->getTypeLabel(),
        ));
    }

    /**
     * @todo: move to application controller
     *
     * @ManagerRoute("/application/{slug}/instance/{instanceId}")
     * @ManagerRoute("/instance/{instanceId}", name="mapbender_manager_repository_unowned_instance", requirements={"instanceId"="\d+"})
     * @ManagerRoute("/instance/{instanceId}/layerset/{layerset}", name="mapbender_manager_repository_unowned_instance_scoped", requirements={"instanceId"="\d+"})
     * @param Request $request
     * @param string|null $slug
     * @param string $instanceId
     * @param Layerset|null $layerset
     * @return Response
     */
    public function instanceAction(Request $request, $instanceId, $slug=null, Layerset $layerset=null)
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
        if (!$instance || ($application && !$application->getSourceInstances(true)->contains($instance))) {
            throw $this->createNotFoundException();
        }
        if (!$layerset && $application) {
            $layerset = $application->getLayersets()->filter(function($layerset) use ($instance) {
                /** @var Layerset $layerset */
                return $layerset->getCombinedInstances()->contains($instance);
            })->first();
        }

        $this->denyAccessUnlessGranted('EDIT', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source'));
        $factory = $this->getTypeDirectory()->getInstanceFactory($instance->getSource());
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

            $this->addFlash('success', 'Your instance has been updated.');
            // redirect to self
            return $this->redirectToRoute($request->attributes->get('_route'), $request->attributes->get('_route_params'));
        }

        return $this->render($factory->getFormTemplate($instance), array(
            "form" => $form->createView(),
            "instance" => $form->getData(),
            'layerset' => $layerset,
        ));
    }

    /**
     * @ManagerRoute("/instance/{instance}/promotetoshared")
     * @param Request $request
     * @param SourceInstance $instance
     * @return Response
     */
    public function promotetosharedinstanceAction(Request $request, SourceInstance $instance)
    {
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
        // @todo: translate flash message
        $this->addFlash('success', "Die Instanz wurde zu einer freien Instanz umgewandelt");
        return $this->redirectToRoute('mapbender_manager_repository_instance', array(
            'instanceId' => $instance->getId(),
            'slug' => $layerset->getApplication()->getSlug(),
        ));
    }

    /**
     * @todo: move to application controller
     *
     * @ManagerRoute("/application/{slug}/instance/{layersetId}/weight/{instanceId}")
     * @param Request $request
     * @param string $slug
     * @param string $layersetId
     * @param string $instanceId
     * @return Response
     */
    public function instanceWeightAction(Request $request, $slug, $layersetId, $instanceId)
    {
        $instanceRepository = $this->getSourceInstanceRepository();
        $instance = $instanceRepository->find($instanceId);

        if (!$instance) {
            throw $this->createNotFoundException('The source instance id:"' . $instanceId . '" does not exist.');
        }

        $layerset = $this->requireLayerset($layersetId);
        return $this->instanceWeightCommon($request, $layerset, $instance);

    }

    /**
     * @todo: move to application controller
     *
     * @ManagerRoute("/layerset/{layerset}/reusable-weight/{assignmentId}")
     * @param Request $request
     * @param Layerset $layerset
     * @param string $assignmentId
     * @return Response
     */
    public function assignmentweightAction(Request $request, Layerset $layerset, $assignmentId)
    {
        $em = $this->getEntityManager();
        /** @var ReusableSourceInstanceAssignment|null $assignment */
        $assignment = $em->getRepository('Mapbender\CoreBundle\Entity\ReusableSourceInstanceAssignment')->find($assignmentId);
        if (!$assignment || !$assignment->getLayerset()) {
            throw $this->createNotFoundException();
        }
        return $this->instanceWeightCommon($request, $layerset, $assignment);
    }

    /**
     * @todo: move to application controller
     *
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
        if (intval($newWeight) === $assignment->getWeight() && $layerset->getId() == $targetLayersetId) {
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        }

        $assignments = $layerset->getCombinedInstanceAssignments();
        if ($layerset->getId() == $targetLayersetId) {
            WeightSortedCollectionUtil::updateSingleWeight($assignments, $assignment, $newWeight);
        } else {
            $targetLayerset = $this->requireLayerset($targetLayersetId);
            $targetAssignments = $targetLayerset->getCombinedInstanceAssignments();
            WeightSortedCollectionUtil::moveBetweenCollections($targetAssignments, $assignments, $assignment, $newWeight);
            $assignment->setLayerset($targetLayerset);
            $em->persist($targetLayerset);
        }
        $em->persist($assignment);
        $em->persist($layerset);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @todo: move to application controller
     *
     * @param Request $request
     * @param Layerset $layerset
     * @param SourceInstanceAssignment $assignment
     * @return Response
     */
    protected function toggleInstanceEnabledCommon(Request $request, Layerset $layerset, SourceInstanceAssignment $assignment)
    {
        if (!$layerset->getApplication()) {
            throw $this->createNotFoundException();
        }
        $application = $layerset->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $layerset->getApplication());
        $em = $this->getEntityManager();
        $newEnabled = $request->get('enabled') === 'true';
        $assignment->setEnabled($newEnabled);
        $application->setUpdated(new \DateTime('now'));
        $em->persist($application);
        $em->persist($assignment);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @todo: move to application controller
     *
     * @ManagerRoute("/application/layerset/{layerset}/instance-enable/{instanceId}", methods={"POST"})
     * @param Request $request
     * @param Layerset $layerset
     * @param string $instanceId
     * @return Response
     */
    public function instanceEnabledAction(Request $request, Layerset $layerset, $instanceId)
    {
        $em = $this->getEntityManager();
        /** @var SourceInstance|null $sourceInstance */
        $sourceInstance = $em->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($instanceId);
        if (!$sourceInstance || !$layerset->getInstances()->contains($sourceInstance)) {
            throw $this->createNotFoundException();
        }
        return $this->toggleInstanceEnabledCommon($request, $layerset, $sourceInstance);
    }

    /**
     * @todo: move to application controller
     *
     * @ManagerRoute("/application/reusable-instance-enable/{assignmentId}", methods={"POST"})
     * @param Request $request
     * @param string $assignmentId
     * @return Response
     */
    public function instanceassignmentenabledAction(Request $request, $assignmentId)
    {
        /** @var ReusableSourceInstanceAssignment|null $assignment */
        $assignment = $this->getEntityManager()->getRepository('Mapbender\CoreBundle\Entity\ReusableSourceInstanceAssignment')->find($assignmentId);
        if (!$assignment || !$assignment->getLayerset()) {
            throw $this->createNotFoundException();
        }
        $layerset = $assignment->getLayerset();
        return $this->toggleInstanceEnabledCommon($request, $layerset, $assignment);
    }

    /**
     * @return TypeDirectoryService
     */
    protected function getTypeDirectory()
    {
        /** @var TypeDirectoryService $service */
        $service = $this->get('mapbender.source.typedirectory.service');
        return $service;
    }

    protected function setAliasForDuplicate(Source $source)
    {
        $wmsWithSameTitle = $this->getDoctrine()
            ->getManager()
            ->getRepository("MapbenderCoreBundle:Source")
            ->findBy(array('title' => $source->getTitle()));

        if (count($wmsWithSameTitle) > 0) {
            $source->setAlias(count($wmsWithSameTitle));
        }
    }

    /**
     * @param object $entity
     */
    protected function initializeAccessControl($entity)
    {
        /** @var MutableAclProviderInterface $aclProvider */
        $aclProvider    = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($entity);
        $acl            = $aclProvider->createAcl($objectIdentity);

        $securityIdentity = UserSecurityIdentity::fromAccount($this->getUser());

        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        $aclProvider->updateAcl($acl);
    }
}
