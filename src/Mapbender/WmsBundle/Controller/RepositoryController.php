<?php

namespace Mapbender\WmsBundle\Controller;

use Doctrine\ORM\EntityRepository;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Component\WmsInstanceEntityHandler;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
use Mapbender\WmsBundle\Entity\WmsOrigin;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Form\Type\WmsSourceSimpleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @ManagerRoute("/repository/wms")
 *
 * @author Christian Wygoda
 */
class RepositoryController extends Controller
{
    public static $WMS_DIR = "xml/wms";

    /**
     * @ManagerRoute("/new")
     * @Method({ "GET" })
     * @Template
     */
    public function newAction()
    {
        $form = $this->createForm(new WmsSourceSimpleType(), new WmsSource());
        return array(
            "form" => $form->createView()
        );
    }

    /**
     * @ManagerRoute("/start")
     * @Method({ "GET" })
     * @Template("MapbenderWmsBundle:Repository:form.html.twig")
     */
    public function startAction()
    {
        $form = $this->createForm(new WmsSourceSimpleType(), new WmsSource());
        return array(
            "form" => $form->createView()
        );
    }

    /**
     * @ManagerRoute("{wms}")
     * @Method({ "GET"})
     * @Template
     */
    public function viewAction(WmsSource $wms)
    {
        $securityContext = $this->get('security.authorization_checker');
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('VIEW', $wms)) {
            throw new AccessDeniedException();
        }
        return array("wms" => $wms);
    }

    /**
     * @ManagerRoute("/create")
     * @Method({ "POST" })
     * @Template("MapbenderWmsBundle:Repository:new.html.twig")
     */
    public function createAction()
    {
        $request       = $this->get('request');
        $wmssource_req = new WmsSource();

        $securityContext = $this->get('security.authorization_checker');
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (false === $securityContext->isGranted('CREATE', $oid)) {
            throw new AccessDeniedException();
        }

        $form      = $this->createForm(new WmsSourceSimpleType(), $wmssource_req);
        $form->submit($request);
        $onlyvalid = $form->get('onlyvalid')->getData();
        if ($form->isValid()) {
            /** @var Importer $importer */
            $importer = $this->container->get('mapbender.importer.source.wms.service');
            $origin = new WmsOrigin($wmssource_req->getOriginUrl(), $wmssource_req->getUsername(), $wmssource_req->getPassword());

            try {
                $importerResponse = $importer->evaluateServer($origin, $onlyvalid);
            } catch (\Exception $e) {
                $this->get("logger")->err($e->getMessage());
                $this->get('session')->getFlashBag()->set('error', $e->getMessage());
                return $this->redirect($this->generateUrl("mapbender_manager_repository_new", array(), true));
            }

            $wmssource = $importerResponse->getWmsSourceEntity();
            $validationError = $importerResponse->getValidationError();
            if ($validationError) {
                $this->get("logger")->warn($validationError->getMessage());
                $this->get('session')->getFlashBag()->set('warning', $validationError->getMessage());
            }

            /** @TODO: this path can never be entered. Why is it here? */
            if (!$wmssource) {
                $this->get("logger")->err('Could not parse data for url "'
                    .$wmssource_req->getOriginUrl().'"');
                $this->get('session')->getFlashBag()
                    ->set('error', 'Could not parse data for url "' . $wmssource_req->getOriginUrl() . '"');
                return $this->redirect($this->generateUrl("mapbender_manager_repository_new", array(), true));
            }
            $this->setAliasForDuplicate($wmssource);

            $this->getDoctrine()->getManager()->getConnection()->beginTransaction();

            $sourceHandler = new WmsSourceEntityHandler($this->container, $wmssource);
            $sourceHandler->save();

            $this->getDoctrine()->getManager()->flush();
            $this->initializeAccessControl($wmssource);
            $this->getDoctrine()->getManager()->getConnection()->commit();
            $this->get('session')->getFlashBag()->set('success', "Your WMS has been created");
            return $this->redirect($this
                ->generateUrl("mapbender_manager_repository_view", array("sourceId" => $wmssource->getId()), true));
        }
        return array(
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * Updates a WMS Source
     * @ManagerRoute("/{sourceId}/updateform")
     * @Template("MapbenderWmsBundle:Repository:updateform.html.twig")
     */
    public function updateformAction($sourceId)
    {
        $source          = $this->loadEntityByPk("MapbenderCoreBundle:Source", $sourceId);
        $securityContext = $this->get('security.authorization_checker');
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('EDIT', $source)) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(new WmsSourceSimpleType(), $source);
        return array(
            "form" => $form->createView()
        );
    }

    /**
     * Updates a WMS Source
     * @ManagerRoute("/{sourceId}/update")
     * @Template("MapbenderWmsBundle:Repository:updateform.html.twig")
     */
    public function updateAction($sourceId)
    {
        $request         = $this->get('request');
        /** @var WmsSource|null $wmsOrig */
        $wmsOrig         = $this->loadEntityByPk("MapbenderCoreBundle:Source", $sourceId);
        $securityContext = $this->get('security.authorization_checker');

        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('EDIT', $wmsOrig)) {
            throw new AccessDeniedException();
        }
        if ($this->getRequest()->getMethod() === 'POST') { // check form and redirect to update
            $wmssource_req = new WmsSource();
            $form          = $this->createForm(new WmsSourceSimpleType(), $wmssource_req);
            $form->submit($request);
            if ($form->isValid()) {
                /** @var Importer $importer */
                $importer = $this->container->get('mapbender.importer.source.wms.service');
                $origin = new WmsOrigin($wmssource_req->getOriginUrl(), $wmssource_req->getUsername(), $wmssource_req->getPassword());
                try {
                    $importerResponse = $importer->evaluateServer($origin, false);
                } catch (\Exception $e) {
                    $this->get("logger")->debug($e->getMessage());
                    $this->get('session')->getFlashBag()->set('error', $e->getMessage());
                    return $this->redirect($this->generateUrl("mapbender_manager_repository_index", array(), true));
                }
                $validationError = $importerResponse->getValidationError();
                if ($validationError) {
                    $this->get("logger")->warn($validationError->getMessage());
                    $this->get('session')->getFlashBag()->set('warning', $validationError->getMessage());
                }
                $wmssource = $importerResponse->getWmsSourceEntity();

                /** @TODO: this path can never be entered. Why is it here? */
                if (!$wmssource) {
                    $this->get("logger")->debug('Could not parse data for url "'.$wmssource_req->getOriginUrl().'"');
                    $this->get('session')->getFlashBag()
                        ->set('error', 'Could not parse data for url "'.$wmssource_req->getOriginUrl().'"');
                    return $this->redirect($this->generateUrl("mapbender_manager_repository_index", array(), true));
                }

                $this->getDoctrine()->getManager()->getConnection()->beginTransaction();
                try {
                    $wmssourcehandler = new WmsSourceEntityHandler($this->container, $wmsOrig);
                    $wmssourcehandler->update($wmssource);
                } catch (\Exception $e) {
                    $this->get("logger")->debug($e->getMessage());
                    $this->get('session')->getFlashBag()->set('error', $e->getMessage());
                    return $this->redirect(
                        $this->generateUrl(
                            "mapbender_manager_repository_updateform",
                            array("sourceId" => $wmsOrig->getId()),
                            true
                        )
                    );
                }
                $this->getDoctrine()->getManager()->persist($wmsOrig);
                $importer->updateOrigin($wmsOrig, $origin);
                $wmsOrig->setValid($wmssource->getValid());

                $wmssourcehandler->save();
                $this->getDoctrine()->getManager()->flush();
                $this->getDoctrine()->getManager()->getConnection()->commit();

                $this->get('session')->getFlashBag()->set('success', 'Your wms source has been updated.');
                return $this->redirect(
                    $this->generateUrl(
                        "mapbender_manager_repository_view",
                        array("sourceId" => $wmsOrig->getId()),
                        true
                    )
                );
            } else {
                return array(
                    "form" => $form->createView()
                );
            }
        } else { // create form for update
            $form = $this->createForm(new WmsSourceSimpleType(), $wmsOrig);
            return array(
                "form" => $form->createView()
            );
        }
    }

    /**
     * Removes a WmsSource
     *
     * @ManagerRoute("/{sourceId}/delete")
     * @Method({"GET"})
     */
    public function deleteAction($sourceId)
    {
        $wmssource    = $this->loadEntityByPk("MapbenderWmsBundle:WmsSource", $sourceId);
        $wmsinstances = $this->getRepository("MapbenderWmsBundle:WmsInstance")
            ->findBy(array('source' => $sourceId));
        $em           = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();

        $aclProvider = $this->get('security.acl.provider');
        $oid         = ObjectIdentity::fromDomainObject($wmssource);
        $aclProvider->deleteAcl($oid);

        foreach ($wmsinstances as $wmsinstance) {
            $wmsinsthandler = new WmsInstanceEntityHandler($this->container, $wmsinstance);
            $wmsinsthandler->remove();
            $em->flush();
        }
        $wmshandler = new WmsSourceEntityHandler($this->container, $wmssource);
        $wmshandler->remove();

        $em->flush();
        $em->getConnection()->commit();
        $this->get('session')->getFlashBag()->set('success', "Your WMS has been deleted");
        return $this->redirect($this->generateUrl("mapbender_manager_repository_index"));
    }

    /**
     * Removes a WmsInstance
     *
     * @ManagerRoute("/{slug}/instance/{instanceId}/delete")
     * @Method({"GET"})
     */
    public function deleteInstanceAction($slug, $instanceId)
    {
        $instance    = $this->loadEntityByPk("MapbenderCoreBundle:SourceInstance", $instanceId);
        $em          = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        $insthandler = new WmsInstanceEntityHandler($this->container, $instance);
        $insthandler->remove();
        $em->flush();
        $em->getConnection()->commit();
        $this->get('session')->getFlashBag()->set('success', 'Your source instance has been deleted.');
        return new Response();
    }

    /**
     * Edits, saves the WmsInstance
     *
     * @ManagerRoute("/instance/{slug}/{instanceId}")
     * @Template("MapbenderWmsBundle:Repository:instance.html.twig")
     */
    public function instanceAction($slug, $instanceId)
    {
        $repositoryName = "MapbenderWmsBundle:WmsInstance";
        /** @var WmsInstance|null $wmsinstance */
        $wmsinstance = $this->loadEntityByPk($repositoryName, $instanceId);

        if ($this->getRequest()->getMethod() == 'POST') { //save
            $form = $this->createForm('wmsinstanceinstancelayers', $wmsinstance);
            $form->submit($this->get('request'));
            if ($form->isValid()) { //save
                $em = $this->getDoctrine()->getManager();
                $em->getConnection()->beginTransaction();
                foreach ($wmsinstance->getLayers() as $layer) {
                    $em->persist($layer);
                    $em->flush();
                    $em->refresh($layer);
                }
                $em->persist($wmsinstance);
                $em->flush();
                $em->getConnection()->commit();
                // reload instance after saving ... why?
                /** @var WmsInstance $wmsinstance */
                $wmsinstance = $this->loadEntityByPk($repositoryName, $wmsinstance->getId());
                $entityHandler = new WmsInstanceEntityHandler($this->container, $wmsinstance);
                $entityHandler->save();
                $em->flush();

                $this->get('session')->getFlashBag()->set('success', 'Your Wms Instance has been changed.');
                return $this
                    ->redirect($this->generateUrl('mapbender_manager_application_edit', array("slug" => $slug)));
            } else { // edit
                $this->get('session')->getFlashBag()->set('warning', 'Your Wms Instance is not valid.');
                return array(
                    "form" => $form->createView(),
                    "slug" => $slug,
                    "instance" => $wmsinstance);
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
            return array(
                "form" => $form->createView(),
                "slug" => $slug,
                "instance" => $wmsinstance,
            );
        }
    }

    /**
     * Changes the priority of WmsInstanceLayers
     *
     * @ManagerRoute("/{slug}/instance/{instanceId}/priority/{instLayerId}")
     */
    public function instanceLayerPriorityAction($slug, $instanceId, $instLayerId)
    {
        $number  = $this->get("request")->get("number");
        /** @var WmsInstanceLayer|null $instLay */
        $instLay = $this->loadEntityByPk('MapbenderWmsBundle:WmsInstanceLayer', $instLayerId);

        if (!$instLay) {
            return new Response(json_encode(array(
                    'error' => 'The wms instance layer with'
                    .' the id "'.$instanceId.'" does not exist.',
                    'result' => '')), 200, array('Content-Type' => 'application/json'));
        }
        if (intval($number) === $instLay->getPriority()) {
            return new Response(json_encode(array(
                    'error' => '',
                    'result' => 'ok')), 200, array('Content-Type' => 'application/json'));
        }
        $em       = $this->getDoctrine()->getManager();
        $instLay->setPriority($number);
        $em->persist($instLay);
        $em->flush();
        $query    = $em->createQuery(
            "SELECT il FROM MapbenderWmsBundle:WmsInstanceLayer il  WHERE il.wmsinstance=:wmsi ORDER BY il.priority ASC"
        );
        $query->setParameters(array("wmsi" => $instanceId));
        /** @var WmsInstanceLayer[] $instList */
        $instList = $query->getResult();

        $num = 0;
        foreach ($instList as $inst) {
            if ($num === intval($instLay->getPriority())) {
                if ($instLay->getId() === $inst->getId()) {
                    $num++;
                } else {
                    $num++;
                    $inst->setPriority($num);
                    $num++;
                }
            } else {
                if ($instLay->getId() !== $inst->getId()) {
                    $inst->setPriority($num);
                    $num++;
                }
            }
        }
        $em->getConnection()->beginTransaction();
        foreach ($instList as $inst) {
            $em->persist($inst);
        }
        $em->flush();
        /** @var WmsInstance $wmsinstance */
        $wmsinstance = $this->loadEntityByPk("MapbenderCoreBundle:SourceInstance", $instanceId);
        $wmsinsthandler = new WmsInstanceEntityHandler($this->container, $wmsinstance);
        $wmsinsthandler->save();
        $em->flush();
        $em->getConnection()->commit();
        return new Response(json_encode(array(
                'error' => '',
                'result' => 'ok')), 200, array(
            'Content-Type' => 'application/json'));
    }

    /**
     * Sets enabled/disabled for the WmsInstance
     *
     * @ManagerRoute("/instance/{slug}/enabled/{instanceId}")
     * @Method({ "POST" })
     */
    public function instanceEnabledAction($slug, $instanceId)
    {
        $enabled     = $this->get("request")->get("enabled");
        $wmsinstance = $this->loadEntityByPk("MapbenderWmsBundle:WmsInstance", $instanceId);
        if (!$wmsinstance) {
            return new Response(
                json_encode(array('error' => 'The wms instance with the id "'.$instanceId.'" does not exist.')),
                200,
                array('Content-Type' => 'application/json')
            );
        } else {
            $enabled_before = $wmsinstance->getEnabled();
            $enabled        = $enabled === "true";
            $wmsinstance->setEnabled($enabled);
            $this->getDoctrine()->getManager()->persist(
                $wmsinstance->getLayerSet()->getApplication()->setUpdated(new \DateTime('now')));
            $this->getDoctrine()->getManager()->persist($wmsinstance);
            $this->getDoctrine()->getManager()->flush();
            return new Response(json_encode(array(
                    'success' => array(
                        "id" => $wmsinstance->getId(),
                        "type" => "instance",
                        "enabled" => array(
                            'before' => $enabled_before,
                            'after' => $enabled)))), 200, array('Content-Type' => 'application/json'));
        }
    }

    /**
     * Get Metadata for a wms service
     *
     * @ManagerRoute("/instance/metadata")
     * @Method({ "POST" })
     */
    public function metadataAction()
    {
        $sourceId        = $this->container->get('request')->get("sourceId", null);
        /** @var SourceInstance|null $instance */
        $instance        = $this->loadEntityByPk('Mapbender\CoreBundle\Entity\SourceInstance', $sourceId);
        $securityContext = $this->get('security.authorization_checker');
        $oid             = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
        if (!$securityContext->isGranted('VIEW', $oid)
            && !$securityContext->isGranted('VIEW', $instance->getLayerset()->getApplication())) {
            throw new AccessDeniedException();
        }
        $layerName = $this->container->get('request')->get("layerName", null);
        $metadata  = $instance->getMetadata();
        $metadata->setContenttype(SourceMetadata::$CONTENTTYPE_ELEMENT);
        $metadata->setContainer(SourceMetadata::$CONTAINER_ACCORDION);
        $content   = $metadata->render($this->container->get('templating'), $layerName);
        $response  = new Response();
        $response->setContent($content);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }

    protected function setAliasForDuplicate(WmsSource $wmsSource)
    {
        $wmsWithSameTitle = $this->getDoctrine()
            ->getManager()
            ->getRepository("MapbenderWmsBundle:WmsSource")
            ->findBy(array('title' => $wmsSource->getTitle()));

        if (count($wmsWithSameTitle) > 0) {
            $wmsSource->setAlias(count($wmsWithSameTitle));
        }
    }

    /**
     * @param object $entity
     */
    protected function initializeAccessControl($entity)
    {
        // ACL
        $aclProvider    = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($entity);
        $acl            = $aclProvider->createAcl($objectIdentity);

        $securityContext  = $this->get('security.token_storage');
        $user             = $securityContext->getToken()->getUser();
        $securityIdentity = UserSecurityIdentity::fromAccount($user);

        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        $aclProvider->updateAcl($acl);
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
