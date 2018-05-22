<?php
namespace Mapbender\CoreBundle\Controller;

use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Controller for site-wide URLs (!= backend, != Application-specific)
 */
class SiteController extends Controller
{
    /**
     * Render imprint.
     *
     * @Route("/imprint")
     *
     * @param Request $request
     * @return Response
     */
    public function imprintAction(Request $request)
    {
        $exposeImprint = $this->container->getParameter('mapbender.imprint.expose');
        if (!$exposeImprint) {
            throw new NotFoundHttpException();
        }
        $contentTemplate = $this->container->getParameter('mapbender.imprint.template.content');
        if ($request->query->get('embed')) {
            $template = $contentTemplate;
            $templateVars = array();
        } else {
            $template = $this->container->getParameter('mapbender.imprint.template.page');
            $templateVars = array(
                'contentTemplate' => $contentTemplate,
            );
        }
        $html = $this->getTemplatingEngine()->render($template, $templateVars);
        return new Response($html);
    }

    /**
     * Render data policy.
     *
     * @Route("/data-policy")
     *
     * @param Request $request
     * @return Response
     */
    public function dataPolicyAction(Request $request)
    {
        $exposeImprint = $this->container->getParameter('mapbender.data_policy.expose');
        if (!$exposeImprint) {
            throw new NotFoundHttpException();
        }
        $contentTemplate = $this->container->getParameter('mapbender.data_policy.template.content');
        if ($request->query->get('embed')) {
            $template = $contentTemplate;
            $templateVars = array();
        } else {
            $template = $this->container->getParameter('mapbender.data_policy.template.page');
            $templateVars = array(
                'contentTemplate' => $contentTemplate,
            );
        }
        $html = $this->getTemplatingEngine()->render($template, $templateVars);
        return new Response($html);
    }

    /**
     * @return TwigEngine
     */
    protected function getTemplatingEngine()
    {
        /** @var TwigEngine $engine */
        $engine = $this->get('templating');
        return $engine;
    }
}
