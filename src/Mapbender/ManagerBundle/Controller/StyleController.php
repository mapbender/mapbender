<?php

namespace Mapbender\ManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Entity\Style;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[ManagerRoute("/styles")]
class StyleController extends ApplicationControllerBase
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em);
    }

    #[ManagerRoute('/', name: 'mapbender_manager_style_index', methods: ['GET'])]
    public function index(): Response
    {
        $styles = $this->em->getRepository(Style::class)->findAll();

        return $this->render('@MapbenderManager/Style/index.html.twig', [
            'styles' => $styles,
        ]);
    }

    #[ManagerRoute('/new', name: 'mapbender_manager_style_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $style = new Style();
        $style->setSourceType('manual');

        if ($request->isMethod('POST')) {
            $style->setName($request->request->get('name'));
            $style->setStyle($request->request->get('style'));

            $this->em->persist($style);
            $this->em->flush();

            $this->addFlash('success', 'Style saved.');
            return $this->redirectToRoute('mapbender_manager_style_index');
        }

        return $this->render('@MapbenderManager/Style/edit.html.twig', [
            'style' => $style,
            '_visual' => $this->extractVisualProps($style),
        ]);
    }

    #[ManagerRoute('/{id}/edit', name: 'mapbender_manager_style_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $style = $this->em->getRepository(Style::class)->find($id);
        if (!$style) {
            throw $this->createNotFoundException();
        }

        if ($style->getSourceType() !== 'manual') {
            return $this->redirectToRoute('mapbender_manager_style_view', ['id' => $id]);
        }

        if ($request->isMethod('POST')) {
            $style->setName($request->request->get('name'));
            $style->setStyle($request->request->get('style'));

            $this->em->flush();

            $this->addFlash('success', 'Style updated.');
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
        $style = $this->em->getRepository(Style::class)->find($id);
        if (!$style) {
            throw $this->createNotFoundException();
        }

        $copy = new Style();
        $copy->setName($style->getName() . ' (Copy)');
        $copy->setStyle($style->getStyle());
        $copy->setSourceType('manual');
        $copy->setSourceId(null);

        $this->em->persist($copy);
        $this->em->flush();

        $this->addFlash('success', 'Style copied.');
        return $this->redirectToRoute('mapbender_manager_style_edit', ['id' => $copy->getId()]);
    }

    #[ManagerRoute('/{id}/delete', name: 'mapbender_manager_style_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $style = $this->em->getRepository(Style::class)->find($id);
        if ($style) {
            $this->em->remove($style);
            $this->em->flush();
            $this->addFlash('success', 'Style deleted.');
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
