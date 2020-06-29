<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use FOM\ManagerBundle\Configuration\Route;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\Repository\LayersetRepository;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceAssignment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class SourceInstanceController extends ApplicationControllerBase
{
    /**
     * @Route("/instance/{instance}", methods={"GET"})
     * @param Request $request
     * @param SourceInstance $instance
     * @return Response
     */
    public function viewAction(Request $request, SourceInstance $instance)
    {
        $viewData = $this->getApplicationRelationViewData($instance);
        return $this->render('@MapbenderManager/SourceInstance/applications.html.twig', $viewData);
    }

    /**
     * @Route("/instance/list/reusable", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function listreusableAction(Request $request)
    {
        /** @todo: specify / implement grants */
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('VIEW', $oid);

        // NOTE: ACL system cannot infer from assignable privilege on abstract Source to privilege
        //       on concrete WmsSource / WmtsSource. DO NOT check grants on concrete source objects.
        $items = $this->getSourceInstanceRepository()->findReusableInstances(array(), array(
            'title' => 'ASC',
            'id' => 'ASC',
        ));

        return $this->render('@MapbenderManager/SourceInstance/list.html.twig', array(
            'title' => $this->getTranslator()->trans('mb.terms.sourceinstance.reusable.plural'),
            'items' => $items,
            // used for DELETE grants check
            'oid' => $oid,
        ));
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
            $em = $this->getEntityManager();
            $em->remove($instance);
            $em->flush();
            if ($returnUrl = $request->query->get('return')) {
                return $this->redirect($returnUrl);
            } else {
                return $this->redirectToRoute('mapbender_manager_sourceinstance_listreusable');
            }
        } else {
            return $this->viewAction($request, $instance);
        }
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
        $em = $this->getEntityManager();
        /** @var TypeDirectoryService $directory */
        $directory = $this->container->get('mapbender.source.typedirectory.service');
        $instance = $directory->createInstance($source);
        $instance->setLayerset(null);
        $em->persist($instance);
        $em->flush();
        $msg = $this->getTranslator()->trans('mb.manager.sourceinstance.created_reusable');
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
        $applicationRepository = $this->getDbApplicationRepository();
        $applicationOrder = array(
            'title' => Criteria::ASC,
            'slug' => Criteria::ASC,
        );
        $viewData = array(
            'layerset_groups' => array(),
        );
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

    /**
     * @return LayersetRepository
     */
    protected function getLayersetRepository()
    {
        /** @var LayersetRepository $repository */
        $repository = $this->getEntityManager()->getRepository('\Mapbender\CoreBundle\Entity\Layerset');
        return $repository;
    }
}
