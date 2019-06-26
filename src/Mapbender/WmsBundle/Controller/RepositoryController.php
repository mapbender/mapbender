<?php

namespace Mapbender\WmsBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\ManagerBundle\Form\Type\HttpSourceOriginType;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @ManagerRoute("/repository/wms")
 *
 * @author Christian Wygoda
 */
class RepositoryController extends Controller
{
    /**
     * @ManagerRoute("{wms}", methods={"GET"})
     * @param WmsSource $wms
     * @return Response
     */
    public function viewAction(WmsSource $wms)
    {
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$this->isGranted('VIEW', $oid) && !$this->isGranted('VIEW', $wms)) {
            throw new AccessDeniedException();
        }
        return $this->render('@MapbenderWms/Repository/view.html.twig', array(
            'wms' => $wms,
        ));
    }

    /**
     * Updates a WMS Source
     * @ManagerRoute("/{sourceId}/updateform")
     * @param string $sourceId
     * @return Response
     */
    public function updateformAction($sourceId)
    {
        /** @var WmsSource $source */
        $source          = $this->loadEntityByPk("MapbenderCoreBundle:Source", $sourceId);
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$this->isGranted('VIEW', $oid) && !$this->isGranted('EDIT', $source)) {
            throw new AccessDeniedException();
        }
        $detectedVersion = UrlUtil::getQueryParameterCaseInsensitive($source->getOriginUrl(), 'version', null);
        if (!$detectedVersion) {
            $amendedUrl = UrlUtil::validateUrl($source->getOriginUrl(), array(
                'VERSION' => $source->getVersion(),
            ));
            $source->setOriginUrl($amendedUrl);
        }

        $form = $this->createForm(new HttpSourceOriginType(), $source);
        return $this->render('@MapbenderWms/Repository/updateform.html.twig',  array(
            "form" => $form->createView()
        ));
    }

    /**
     * Updates a WMS Source
     * @ManagerRoute("/{sourceId}/update")
     * @param Request $request
     * @param string $sourceId
     * @return Response
     */
    public function updateAction(Request $request, $sourceId)
    {
        /** @var WmsSource|null $wmsOrig */
        $wmsOrig         = $this->loadEntityByPk("MapbenderCoreBundle:Source", $sourceId);

        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$this->isGranted('VIEW', $oid) && !$this->isGranted('EDIT', $wmsOrig)) {
            throw new AccessDeniedException();
        }
        $formModel = HttpOriginModel::extract($wmsOrig);
        $form = $this->createForm(new HttpSourceOriginType(), $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Importer $importer */
            $importer = $this->container->get('mapbender.importer.source.wms.service');
            try {
                $importerResponse = $importer->evaluateServer($formModel, false);
            } catch (\Exception $e) {
                $this->get("logger")->debug($e->getMessage());
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute("mapbender_manager_repository_index");
            }
            $wmssource = $importerResponse->getWmsSourceEntity();

            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            $em->beginTransaction();
            try {
                $wmssourcehandler = new WmsSourceEntityHandler($this->container, null);
                $wmssourcehandler->updateSource($wmsOrig, $wmssource);
            } catch (\Exception $e) {
                $this->get("logger")->debug($e->getMessage());
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute("mapbender_manager_repository_updateform", array(
                    "sourceId" => $wmsOrig->getId(),
                ));
            }
            $em->persist($wmsOrig);
            $importer->updateOrigin($wmsOrig, $formModel);
            $wmsOrig->setValid($wmssource->getValid());

            $em->flush();
            $em->commit();

            $this->addFlash('success', 'Your wms source has been updated.');
            return $this->redirectToRoute("mapbender_manager_repository_view", array(
                "sourceId" => $wmsOrig->getId(),
            ));
        } else { // create form for update
            return $this->render('@MapbenderWms/Repository/updateform.html.twig', array(
                "form" => $form->createView()
            ));
        }
    }

    /**
     * Edits, saves the WmsInstance
     *
     * @ManagerRoute("/instance/{slug}/{instanceId}")
     * @param Request $request
     * @param string $slug
     * @param string $instanceId
     * @return Response
     */
    public function instanceAction(Request $request, $slug, $instanceId)
    {
        $repositoryName = "MapbenderWmsBundle:WmsInstance";
        /** @var WmsInstance|null $wmsinstance */
        $wmsinstance = $this->loadEntityByPk($repositoryName, $instanceId);

        if ($request->getMethod() == 'POST') { //save
            $form = $this->createForm('wmsinstanceinstancelayers', $wmsinstance);
            $form->submit($request);
            if ($form->isValid()) { //save
                $em = $this->getDoctrine()->getManager();
                $em->getConnection()->beginTransaction();
                $em->persist($wmsinstance);
                $layerSet = $wmsinstance->getLayerset();
                if ($layerSet) {
                    $application = $layerSet->getApplication();
                    if ($application) {
                        $application->setUpdated(new \DateTime('now'));
                        $em->persist($application);
                    }
                }
                $em->flush();
                $em->getConnection()->commit();

                $this->addFlash('success', 'Your Wms Instance has been changed.');
                return $this->redirectToRoute('mapbender_manager_application_edit', array(
                    "slug" => $slug,
                ));
            } else { // edit
                $this->addFlash('warning', 'Your Wms Instance is not valid.');
                return $this->render('@MapbenderWms/Repository/instance.html.twig', array(
                    "form" => $form->createView(),
                    "slug" => $slug,
                    "instance" => $wmsinstance,
                ));
            }
        } else { // edit
            /* bug fix start @TODO remove after migration's introduction */
            foreach ($wmsinstance->getLayers() as $layer) {
                if ($layer->getSublayer()->count() === 0) {
                    $layer->setToggle(null);
                    $layer->setAllowtoggle(null);
                }
            }
            /* bug fix end */
            $form = $this->createForm('wmsinstanceinstancelayers', $wmsinstance);
            return $this->render('@MapbenderWms/Repository/instance.html.twig', array(
                "form" => $form->createView(),
                "slug" => $slug,
                "instance" => $wmsinstance,
            ));
        }
    }

    /**
     * @param string $repositoryName
     * @param mixed $id
     * @return object|null
     */
    protected function loadEntityByPk($repositoryName, $id)
    {
        return $this->getDoctrine()->getRepository($repositoryName)->find($id);
    }

    /**
     * @param string $repositoryName
     * @param string $persistentManagerName object manager name (leave as null for default manager)
     * @return EntityRepository
     */
    protected function getRepository($repositoryName, $persistentManagerName = null)
    {
        return $this->getDoctrine()->getRepository($repositoryName, $persistentManagerName);
    }
}
