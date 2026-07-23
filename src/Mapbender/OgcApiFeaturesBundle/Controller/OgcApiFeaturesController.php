<?php

namespace Mapbender\OgcApiFeaturesBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\ManagerBundle\Controller\ApplicationControllerBase;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstance;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;
use Mapbender\OgcApiFeaturesBundle\Form\Type\OgcApiFeaturesInstanceLayerSettingsType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OgcApiFeaturesController extends ApplicationControllerBase
{
    public function __construct(
        EntityManagerInterface $em,
    )
    {
        parent::__construct($em);
    }

    #[ManagerRoute('/ogcapifeatures/{instanceLayerId}/settings', name: 'mapbender_ogcapifeatures_editsettings', requirements: ['instanceLayerId' => '\d+'], methods: ['GET', 'POST'])]
    public function editSettings(Request $request, int $instanceLayerId): Response
    {
        $repo = $this->em->getRepository(OgcApiFeaturesInstanceLayer::class);
        /** @var OgcApiFeaturesInstanceLayer $instanceLayer */
        $instanceLayer = $repo->find($instanceLayerId);
        if (!$instanceLayer) {
            throw $this->createNotFoundException('Instance layer not found');
        }

        $application = $repo->createQueryBuilder('il')
            ->where('il.id = :id')
            ->setParameter('id', $instanceLayerId)
            ->leftJoin(OgcApiFeaturesInstance::class, 'fi', 'WITH', 'il.sourceInstance = fi.id')
            ->leftJoin(Layerset::class, 'l', 'WITH', 'fi.layerset = l.id')
            ->leftJoin(Application::class, 'a', 'WITH', 'l.application = a.id')
            ->select('a')
            ->getQuery()
            ->getOneOrNullResult();

        if ($application) {
            $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);
        } else {
            $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES);
        }

        $form = $this->createForm(OgcApiFeaturesInstanceLayerSettingsType::class, $instanceLayer);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            return new JsonResponse([
                'hasStyle' => $instanceLayer->getHasStyle(),
                'secondaryStyleCount' => $instanceLayer->getSecondaryStyleCount(),
            ]);
        }

        return $this->render('@MapbenderOgcApiFeatures/instance-settings.html.twig', [
            "form" => $form->createView(),
            "layer" => $form->getData(),
        ]);
    }

}
