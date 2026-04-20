<?php

namespace Mapbender\ManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\Style;
use Mapbender\ManagerBundle\Form\Type\StyleType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
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
        $style->setStyle(json_encode($this->getDefaultStyleProperties(), JSON_PRETTY_PRINT));

        $form = $this->createForm(StyleType::class, $style);
        $form->handleRequest($request);

        $styleJsonValid = !$form->isSubmitted() || $this->validateStyleJson($form, $style);

        if ($form->isSubmitted() && $form->isValid() && $styleJsonValid) {
            $this->em->persist($style);
            $this->em->flush();

            $this->addFlash('success', $this->trans->trans('mb.ogcapifeatures.admin.style.saved'));
            return $this->redirectToRoute('mapbender_manager_style_index');
        }

        return $this->render('@MapbenderManager/Style/edit.html.twig', [
            'form' => $form->createView(),
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

        if (!in_array($style->getSourceType(), ['manual', 'mapbox-json'], true)) {
            return $this->redirectToRoute('mapbender_manager_style_view', ['id' => $id]);
        }

        $form = $this->createForm(StyleType::class, $style);
        $form->handleRequest($request);

        $styleJsonValid = !$form->isSubmitted() || $this->validateStyleJson($form, $style);

        if ($form->isSubmitted() && $form->isValid() && $styleJsonValid) {
            $this->em->flush();

            $this->addFlash('success', $this->trans->trans('mb.ogcapifeatures.admin.style.updated'));
            return $this->redirectToRoute('mapbender_manager_style_index');
        }

        return $this->render('@MapbenderManager/Style/edit.html.twig', [
            'form' => $form->createView(),
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

        $form = $this->createForm(StyleType::class, $style);

        return $this->render('@MapbenderManager/Style/edit.html.twig', [
            'form' => $form->createView(),
            'style' => $style,
            '_visual' => $this->extractVisualProps($style),
            'readonly' => true,
        ]);
    }

    #[ManagerRoute('/{id}/copy', name: 'mapbender_manager_style_copy', methods: ['POST'])]
    public function copy(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_STYLES);
        if (!$this->isCsrfTokenValid('style_copy', $request->request->get('_token'))) {
            throw new InvalidCsrfTokenException();
        }
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
    public function delete(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_DELETE_STYLES);
        if (!$this->isCsrfTokenValid('style_delete', $request->request->get('_token'))) {
            throw new InvalidCsrfTokenException();
        }
        $style = $this->em->getRepository(Style::class)->find($id);
        if ($style) {
            if ($style->getSourceId()) {
                $source = $this->em->getRepository(Source::class)->find($style->getSourceId());
                if ($source) {
                    $this->addFlash('error', $this->trans->trans('mb.ogcapifeatures.admin.style.cannot_delete_source_style'));
                    return $this->redirectToRoute('mapbender_manager_style_index');
                }
            }
            $this->em->remove($style);
            $this->em->flush();
            $this->addFlash('success', $this->trans->trans('mb.ogcapifeatures.admin.style.deleted'));
        }

        return $this->redirectToRoute('mapbender_manager_style_index');
    }

    private function extractVisualProps(Style $style): array
    {
        $defaults = $this->getDefaultStyleProperties();
        $json = $style->getStyle() ? json_decode($style->getStyle(), true) : [];
        if (!is_array($json)) {
            $json = [];
        }
        return array_merge($defaults, array_intersect_key($json, $defaults));
    }

    protected function getDefaultStyleProperties(): array
    {
        return [
            'fillColor' => '#0099ff',
            'fillOpacity' => 0.4,
            'pointRadius' => 5,
            'strokeColor' => '#3399cc',
            'strokeOpacity' => 1,
            'strokeWidth' => 1.25,
            'strokeLinecap' => 'round',
            'strokeDashstyle' => 'solid',
            'label' => '',
            'fontFamily' => 'Arial, Helvetica, sans-serif',
            'fontSize' => 11,
            'fontWeight' => 'regular',
            'fontColor' => '#333333',
            'fontOpacity' => 1,
        ];
    }

    private function validateStyleJson(FormInterface $form, Style $style): bool
    {
        $raw = (string) $style->getStyle();
        if ('' === trim($raw)) {
            $form->get('style')->addError(new FormError($this->trans->trans(
                'mb.ogcapifeatures.admin.style.editor.invalid_json',
                ['error' => 'empty input']
            )));
            return false;
        }

        try {
            json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            $form->get('style')->addError(new FormError($this->trans->trans(
                'mb.ogcapifeatures.admin.style.editor.invalid_json',
                ['error' => $e->getMessage()]
            )));
            return false;
        }
    }
}
