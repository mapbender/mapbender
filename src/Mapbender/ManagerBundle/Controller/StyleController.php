<?php

namespace Mapbender\ManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\CoreBundle\Entity\Style;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[ManagerRoute("/styles")]
class StyleController extends ApplicationControllerBase
{
    public function __construct(
        EntityManagerInterface $em,
        protected TranslatorInterface $trans,
    ) {
        parent::__construct($em);
    }

    #[ManagerRoute('/', name: 'mapbender_manager_style_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_VIEW_STYLES);
        $styles = $this->em->getRepository(Style::class)->findAll();

        return $this->render('@MapbenderManager/Style/index.html.twig', [
            'styles' => $styles,
            'grants' => [
                'create' => $this->isGranted(ResourceDomainInstallation::ACTION_CREATE_STYLES),
                'edit' => $this->isGranted(ResourceDomainInstallation::ACTION_EDIT_STYLES),
                'delete' => $this->isGranted(ResourceDomainInstallation::ACTION_DELETE_STYLES),
            ],
        ]);
    }

    #[ManagerRoute('/json', name: 'mapbender_manager_style_json', methods: ['GET'])]
    public function jsonList(): JsonResponse
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_VIEW_STYLES);
        $styles = $this->em->getRepository(Style::class)->findAll();
        $map = [];
        foreach ($styles as $style) {
            $map[$style->getId()] = [
                'style' => $style->getStyle(),
                'collectionId' => $style->getCollectionId(),
                'name' => $style->getName(),
                'sourceType' => $style->getSourceType(),
                'sourceId' => $style->getSourceId(),
            ];
        }
        return new JsonResponse($map);
    }

    #[ManagerRoute('/new', name: 'mapbender_manager_style_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_STYLES);
        $style = new Style();
        $style->setSourceType('manual');

        // OpenLayers default style as initial value
        $defaultStyle = json_encode([
            'fillColor' => '#0099ff',
            'fillOpacity' => 0.4,
            'strokeColor' => '#3399cc',
            'strokeWidth' => 1.25,
            'strokeOpacity' => 1,
            'strokeDashstyle' => 'solid',
            'strokeLinecap' => 'round',
            'pointRadius' => 5,
            'label' => '',
            'fontFamily' => 'Arial, Helvetica, sans-serif',
            'fontSize' => 11,
            'fontWeight' => 'regular',
            'fontColor' => '#333333',
            'fontOpacity' => 1,
        ], JSON_PRETTY_PRINT);
        $style->setStyle($defaultStyle);

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            if ($name === '') {
                $this->addFlash('error', $this->trans->trans('mb.ogcapifeatures.admin.style.name_required'));
                $style->setStyle($request->request->get('style'));
                return $this->render('@MapbenderManager/Style/edit.html.twig', [
                    'style' => $style,
                    '_visual' => $this->extractVisualProps($style),
                    'nameError' => true,
                ]);
            }
            $style->setName($name);
            $style->setStyle($request->request->get('style'));
            $sourceType = $request->request->get('sourceType');
            if ($sourceType) {
                $style->setSourceType($sourceType);
            }

            $this->em->persist($style);
            $this->em->flush();

            $this->addFlash('success', $this->trans->trans('mb.ogcapifeatures.admin.style.saved'));
            return $this->redirectToRoute('mapbender_manager_style_index');
        }

        return $this->render('@MapbenderManager/Style/edit.html.twig', [
            'style' => $style,
            '_visual' => $this->extractVisualProps($style),
            'isNew' => true,
        ]);
    }

    #[ManagerRoute('/{id}/edit', name: 'mapbender_manager_style_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_EDIT_STYLES);
        $style = $this->em->getRepository(Style::class)->find($id);
        if (!$style) {
            throw $this->createNotFoundException();
        }

        if ($style->getSourceType() !== 'manual' && !in_array($style->getSourceType(), ['mapbox-json', 'sld'], true)) {
            return $this->redirectToRoute('mapbender_manager_style_view', ['id' => $id]);
        }

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            if ($name === '') {
                $this->addFlash('error', $this->trans->trans('mb.ogcapifeatures.admin.style.name_required'));
                $style->setStyle($request->request->get('style'));
                return $this->render('@MapbenderManager/Style/edit.html.twig', [
                    'style' => $style,
                    '_visual' => $this->extractVisualProps($style),
                    'nameError' => true,
                ]);
            }
            $style->setName($name);
            $style->setStyle($request->request->get('style'));
            $sourceType = $request->request->get('sourceType');
            if ($sourceType) {
                $style->setSourceType($sourceType);
            }

            $this->em->flush();

            $this->addFlash('success', $this->trans->trans('mb.ogcapifeatures.admin.style.updated'));
            return $this->redirectToRoute('mapbender_manager_style_index');
        }

        return $this->render('@MapbenderManager/Style/edit.html.twig', [
            'style' => $style,
            '_visual' => $this->extractVisualProps($style),
        ]);
    }

    #[ManagerRoute('/{id}/view', name: 'mapbender_manager_style_view', methods: ['GET'])]
    public function view(int $id): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_VIEW_STYLES);
        $style = $this->em->getRepository(Style::class)->find($id);
        if (!$style) {
            throw $this->createNotFoundException();
        }

        return $this->render('@MapbenderManager/Style/edit.html.twig', [
            'style' => $style,
            '_visual' => $this->extractVisualProps($style),
            'readonly' => true,
        ]);
    }

    #[ManagerRoute('/{id}/copy', name: 'mapbender_manager_style_copy', methods: ['POST'])]
    public function copy(int $id): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_STYLES);
        $style = $this->em->getRepository(Style::class)->find($id);
        if (!$style) {
            throw $this->createNotFoundException();
        }

        $copy = new Style();
        $copy->setName($style->getName() . ' ' . $this->trans->trans('mb.ogcapifeatures.admin.style.copy_suffix'));
        $copy->setStyle($style->getStyle());
        $copy->setSourceType('manual');
        $copy->setSourceId(null);

        $this->em->persist($copy);
        $this->em->flush();

        $this->addFlash('success', $this->trans->trans('mb.ogcapifeatures.admin.style.copied'));
        return $this->redirectToRoute('mapbender_manager_style_edit', ['id' => $copy->getId()]);
    }

    #[ManagerRoute('/{id}/delete', name: 'mapbender_manager_style_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_DELETE_STYLES);
        $style = $this->em->getRepository(Style::class)->find($id);
        if ($style) {
            $this->em->remove($style);
            $this->em->flush();
            $this->addFlash('success', $this->trans->trans('mb.ogcapifeatures.admin.style.deleted'));
        }

        return $this->redirectToRoute('mapbender_manager_style_index');
    }

    private function extractVisualProps(Style $style): array
    {
        $defaults = [
            'fillColor' => '#ff0000',
            'fillOpacity' => 1,
            'pointRadius' => 5,
            'strokeColor' => '#ffffff',
            'strokeOpacity' => 1,
            'strokeWidth' => 1,
            'strokeLinecap' => 'round',
            'strokeDashstyle' => 'solid',
            'label' => '',
            'fontFamily' => 'Arial, Helvetica, sans-serif',
            'fontSize' => 11,
            'fontWeight' => 'regular',
            'fontColor' => '#000000',
            'fontOpacity' => 1,
        ];
        $json = $style->getStyle() ? json_decode($style->getStyle(), true) : [];
        if (!is_array($json)) {
            $json = [];
        }
        return array_merge($defaults, array_intersect_key($json, $defaults));
    }
}
