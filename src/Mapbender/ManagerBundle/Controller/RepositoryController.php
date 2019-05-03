<?php
namespace Mapbender\ManagerBundle\Controller;

use Mapbender\CoreBundle\Mapbender;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 *  Mapbender repository controller
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 * @ManagerRoute("/repository")
 */
class RepositoryController extends Controller
{
    /**
     * Renders the layer service repository.
     *
     * @ManagerRoute("/{page}", defaults={ "page"=1 }, requirements={ "page"="\d+" }, methods={"GET"})
     * @return Response
     */
    public function indexAction($page)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $repository = $this->getDoctrine()->getRepository('Mapbender\CoreBundle\Entity\Source');
        $sources = $repository->findBy(array(), array('id' => 'ASC'));

        $allowed_sources = array();
        foreach ($sources as $source) {
            if (!$this->isGranted('VIEW', $oid) && !$this->isGranted('VIEW', $source)) {
                continue;
            }
            $allowed_sources[] = $source;
        }

        return $this->render('@MapbenderManager/Repository/index.html.twig', array(
            'title' => 'Repository',
            'sources' => $allowed_sources,
            'oid' => $oid,
            'create_permission' => $this->isGranted('CREATE', $oid),
        ));
    }

    /**
     * @ManagerRoute("/new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('CREATE', $oid);

        $managers = $this->getRepositoryManagers();
        return $this->render('@MapbenderManager/Repository/new.html.twig', array(
            'managers' => $managers
        ));
    }

    /**
     * @ManagerRoute("/create/{managertype}", methods={"POST"})
     * @param string $managertype
     * @return Response
     */
    public function createAction($managertype)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $this->denyAccessUnlessGranted('CREATE', $oid);

        $managers = $this->getRepositoryManagers();
        $manager = $managers[$managertype];

        return $this->forward($manager['bundle'] . ":" . "Repository:create");
    }

    /**
     * @ManagerRoute("/source/{sourceId}", methods={"GET"})
     * @param string $sourceId
     * @return Response
     */
    public function viewAction($sourceId)
    {
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        $managers = $this->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        return $this->forward($manager['bundle'] . ":" . "Repository:view", array(
            "id" => $source->getId(),
        ));
    }

    /**
     * deletes a Source
     * @ManagerRoute("/source/{sourceId}/confirmdelete", methods={"GET"})
     * @param string $sourceId
     * @return Response
     */
    public function confirmdeleteAction($sourceId)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
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
        return $this->render('@MapbenderManager/Repository/confirmdelete.html.twig',  array(
            'source' => $source,
        ));
    }

    /**
     * deletes a Source
     * @ManagerRoute("/source/{sourceId}/delete", methods={"POST"})
     * @param string $sourceId
     * @return Response
     */
    public function deleteAction($sourceId)
    {
        /** @todo: fold identical preface code shared with confirmdeleteAction */
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
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
        // -- common preface code end --

        $managers = $this->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        return $this->forward($manager['bundle'] . ":" . "Repository:delete", array(
            "sourceId" => $source->getId(),
        ));
    }

    /**
     * Returns a Source update form.
     *
     * @ManagerRoute("/source/{sourceId}/updateform", methods={"GET"})
     * @param string $sourceId
     * @return Response
     */
    public function updateformAction($sourceId)
    {
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $source = $this->getDoctrine()->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        if (!$source) {
            // If edit action is forbidden, hide the fact that the source doesn't
            // exist behind an access denied.
            $this->denyAccessUnlessGranted('VIEW', $oid);
            $this->denyAccessUnlessGranted('EDIT', $oid);
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

        $managers = $this->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        return $this->render('@MapbenderManager/Repository/updateform.html.twig', array(
            'manager' => $manager,
            'source' => $source
        ));
    }

    /**
     * Updates a Source
     *
     * @ManagerRoute("/source/{sourceId}/update", methods={"POST"})
     * @param string $sourceId
     * @return Response
     */
    public function updateAction($sourceId)
    {
        /** @todo: fold identical preface code shared with updateformAction */
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $source = $this->getDoctrine()->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        if (!$source) {
            // If edit action is forbidden, hide the fact that the source doesn't
            // exist behind an access denied.
            $this->denyAccessUnlessGranted('VIEW', $oid);
            $this->denyAccessUnlessGranted('EDIT', $oid);
            throw $this->createNotFoundException();
        }
        if (!$this->isGranted('VIEW', $oid)) {
            $this->denyAccessUnlessGranted('VIEW', $source);
        }
        if (!$this->isGranted('EDIT', $oid)) {
            $this->denyAccessUnlessGranted('EDIT', $source);
        }

        $managers = $this->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        // -- common preface code end --
        return $this->forward($manager['bundle'] . ":" . "Repository:update", array(
            "sourceId" => $source->getId(),
        ));
    }

    /**
     *
     * @ManagerRoute("/application/{slug}/instance/{instanceId}")
     * @param string $slug
     * @param string $instanceId
     * @return Response
     */
    public function instanceAction($slug, $instanceId)
    {
        $sourceInst = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);

        if (null === $sourceInst) {
            throw $this->createNotFoundException('Instance does not exist');
        }

        $this->denyAccessUnlessGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source'));

        $managers = $this->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];

        return $this->forward($manager['bundle'] . ":" . "Repository:instance", array(
            "slug" => $slug,
            "instanceId" => $sourceInst->getId(),
        ));
    }

    /**
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

        $newWeight = $request->get("number");
        $targetLayersetId = $request->get("new_layersetId");
        $em = $this->getDoctrine()->getManager();
        /** @var EntityRepository $instanceRepository */
        $instanceRepository = $this->getDoctrine()->getRepository('MapbenderCoreBundle:SourceInstance');
        $lsRepository = $this->getDoctrine()->getRepository('MapbenderCoreBundle:Layerset');

        /** @var SourceInstance $instance */
        $instance = $instanceRepository->findOneBy(array('id' => $instanceId));

        if (!$instance) {
            throw $this->createNotFoundException('The source instance id:"' . $instanceId . '" does not exist.');
        }
        if (intval($newWeight) === $instance->getWeight() && $layersetId === $targetLayersetId) {
            return new JsonResponse(array(
                'error' => '',      // why?
                'result' => 'ok',   // why?
            ));
        }

        /** @var Layerset $layerset */
        $layerset = $lsRepository->findOneBy(array('id' => $layersetId));
        if ($layersetId === $targetLayersetId) {
            WeightSortedCollectionUtil::updateSingleWeight($layerset->getInstances(), $instance, $newWeight);
        } else {
            /** @var Layerset $targetLayerset */
            $targetLayerset = $lsRepository->findOneBy(array('id' => $targetLayersetId));
            $targetCollection = $targetLayerset->getInstances();
            WeightSortedCollectionUtil::moveBetweenCollections($targetCollection, $layerset->getInstances(), $instance, $newWeight);
            $instance->setLayerset($targetLayerset);
            $em->persist($targetLayerset);
        }
        $em->persist($layerset);
        $em->flush();

        return new JsonResponse(array(
            'error' => '',      // why?
            'result' => 'ok',   // why?
        ));
    }

    /**
     *
     * @ManagerRoute("/application/{slug}/instance/{layersetId}/enabled/{instanceId}", methods={"POST"})
     * @param string $slug
     * @param string $layersetId
     * @param string $instanceId
     * @return Response
     */
    public function instanceEnabledAction($slug, $layersetId, $instanceId)
    {
        $sourceInst = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);
        $managers = $this->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];

        return $this->forward($manager['bundle'] . ":" . "Repository:instanceenabled", array(
            "slug" => $slug,
            "layersetId" => $layersetId,
            "instanceId" => $sourceInst->getId(),
        ));
    }

    /**
     *
     * @ManagerRoute("/application/{slug}/instanceLayer/{instanceId}/weight/{instLayerId}")
     * @param string $slug
     * @param string $instanceId
     * @param string $instLayerId
     * @return Response
     */
    public function instanceLayerWeightAction($slug, $instanceId, $instLayerId)
    {
        $sourceInst = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);
        $managers = $this->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];

        return $this->forward($manager['bundle'] . ":" . "Repository:instancelayerpriority", array(
            "slug" => $slug,
            "instanceId" => $sourceInst->getId(),
            "instLayerId" => $instLayerId
        ));
    }

    /**
     * @return array[]
     */
    protected function getRepositoryManagers()
    {
        /** @var Mapbender $service */
        $service = $this->get('mapbender');
        return $service->getRepositoryManagers();
    }
}
