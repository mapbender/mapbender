<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use FOM\ManagerBundle\Configuration\Route;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceAssignment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Translation\TranslatorInterface;

class SourceInstanceController extends ApplicationControllerBase
{
    /** @var TranslatorInterface */
    protected $translator;
    /** @var TypeDirectoryService */
    protected $typeDirectory;


    public function __construct(TranslatorInterface $translator,
                                TypeDirectoryService $typeDirectory)
    {
        $this->translator = $translator;
        $this->typeDirectory = $typeDirectory;
    }

    /**
     * @Route("/instance/{instance}", methods={"GET"})
     * @param SourceInstance $instance
     * @return Response
     */
    public function viewAction(SourceInstance $instance)
    {
        $viewData = $this->getApplicationRelationViewData($instance);
        return $this->render('@MapbenderManager/SourceInstance/applications.html.twig', $viewData);
    }

    /**
     * @Route("/instance/{instance}/delete", methods={"GET", "POST"})
     * @param Request $request
     * @param SourceInstance $instance
     * @return Response
     */
    public function deleteAction(Request $request, SourceInstance $instance)
    {
        /** @todo: specify / implement proper grants */
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('DELETE', $oid);
        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($instance);
            $em->flush();
            if ($returnUrl = $request->query->get('return')) {
                return $this->redirect($returnUrl);
            } else {
                return $this->redirectToRoute('mapbender_manager_repository_index', array(
                    '_framgent' => 'tabSharedInstances',
                ));
            }
        } else {
            return $this->viewAction($instance);
        }
    }

    /**
     * Add a new SourceInstance to the Layerset
     * @Route("/application/{slug}/layerset/{layersetId}/source/{sourceId}/add",
     *     name="mapbender_manager_application_addinstance",
     *     methods={"GET"})
     *
     * @param Request $request
     * @param string $slug of Application
     * @param int $layersetId
     * @param int $sourceId
     * @return Response
     */
    public function addInstanceAction(Request $request, $slug, $layersetId, $sourceId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var Application|null $application */
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if ($application) {
            $this->denyAccessUnlessGranted('EDIT', $application);
        } else {
            throw $this->createNotFoundException();
        }
        $layerset = $this->requireLayerset($layersetId, $application);
        /** @var Source|null $source */
        $source = $this->getDoctrine()->getRepository(Source::class)->find($sourceId);
        $newInstance = $this->typeDirectory->createInstance($source);
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
        $this->addFlash('success', $this->translator->trans('mb.source.instance.create.success'));
        return $this->redirectToRoute("mapbender_manager_repository_instance", array(
            "slug" => $slug,
            "instanceId" => $newInstance->getId(),
        ));
    }

    /**
     * @Route("/instance/createshared/{source}", methods={"GET", "POST"}))
     * @param Request $request
     * @param Source $source
     * @return Response
     */
    public function createsharedAction(Request $request, Source $source)
    {
        // @todo: only act on post
        $em = $this->getDoctrine()->getManager();
        $instance = $this->typeDirectory->createInstance($source);
        $instance->setLayerset(null);
        $em->persist($instance);
        $em->flush();
        $msg = $this->translator->trans('mb.manager.sourceinstance.created_reusable');
        $this->addFlash('success', $msg);
        return $this->redirectToRoute('mapbender_manager_repository_unowned_instance', array(
            'instanceId' => $instance->getId(),
        ));
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
        /** @var ApplicationRepository $applicationRepository */
        $applicationRepository = $this->getDoctrine()->getRepository(Application::class);
        $relatedApplications = $applicationRepository->findWithSourceInstance($instance, null, $applicationOrder);
        foreach ($relatedApplications as $application) {
            /** @var Layerset[] $relatedLayersets */
            $relatedLayersets = $application->getLayersets()->filter(function($layerset) use ($instance) {
                /** @var Layerset $layerset */
                return $layerset->getCombinedInstances()->contains($instance);
            })->getValues();
            if (!$relatedLayersets) {
                throw new \LogicException("Instance => Application lookup error; should contain instance #{$instance->getId()}, but doesn't");
                continue;
            }
            $appViewData = array(
                'application' => $application,
                'instance_groups' => array(),
            );
            foreach ($relatedLayersets as $ls) {
                $layersetViewData = array(
                    'layerset' => $ls,
                    'instance_assignments' => array(),
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
}
