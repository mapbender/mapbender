<?php

namespace Mapbender\OgcApiFeaturesBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\ManagerBundle\Controller\ApplicationControllerBase;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;
use Mapbender\OgcApiFeaturesBundle\Form\Type\OgcApiFeaturesInstanceLayerSettingsType;
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
        // TODO: Free instances, Permission checks

        /** @var OgcApiFeaturesInstanceLayer $instanceLayer */
        $instanceLayer = $this->em->getRepository(OgcApiFeaturesInstanceLayer::class)->find($instanceLayerId);
        if (!$instanceLayer) {
            throw $this->createNotFoundException('Instance layer not found');
        }

        if (false) {
            $application = $this->requireDbApplication($slug);
            $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_EDIT, $application);

            $layerSource = $instanceLayer->getSourceItem()->getSource();
            $sourceInstances = $application->getInstancesOfSource($layerSource);
            if (count($sourceInstances) < 1) {
                throw $this->createNotFoundException('Layer not found in application');
            }
        }

        $form = $this->createForm(OgcApiFeaturesInstanceLayerSettingsType::class, $instanceLayer);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return $this->render('@MapbenderOgcApiFeatures/instance-settings.html.twig', [
            "form" => $form->createView(),
            "layer" => $form->getData(),
        ]);
    }

}
