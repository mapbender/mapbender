<?php


namespace Mapbender\ManagerBundle\Controller;


use FOM\ManagerBundle\Configuration\Route;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class SourceInstanceController extends ApplicationControllerBase
{
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

        $items = array();
        foreach ($this->getSourceInstanceRepository()->findReusableInstances() as $item) {
            if ($this->isGranted('VIEW', $item->getSource())) {
                $items[] = $item;
            }
        }

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
        $this->denyAccessUnlessGranted('DELETE', $instance->getSource());
        $em = $this->getEntityManager();
        $em->remove($instance);
        $em->flush();
        if ($returnUrl = $request->query->get('return')) {
            return $this->redirect($returnUrl);
        } else {
            return $this->redirectToRoute('mapbender_manager_sourceinstance_listreusable');
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
        $this->addFlash('success', "Neue freie Instanz erzeugt");
        return $this->redirectToRoute('mapbender_manager_repository_unowned_instance', array(
            'instanceId' => $instance->getId(),
        ));
    }
}
