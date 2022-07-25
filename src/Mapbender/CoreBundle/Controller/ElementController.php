<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Mapbender\FrameworkBundle\Component\Renderer\ElementMarkupRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ElementController extends YamlApplicationAwareController
{
    /** @var ElementFilter */
    protected $filter;
    /** @var ElementMarkupRenderer */
    protected $renderer;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                ElementFilter $filter,
                                ElementMarkupRenderer $renderer)
    {
        parent::__construct($yamlRepository);
        $this->filter = $filter;
        $this->renderer = $renderer;
    }

    /**
     * Element action controller.
     *
     * Passes the request to
     * the element's handleHttpRequest.
     * @Route("/application/{slug}/element/{id}/{action}",
     *     name="mapbender_core_application_element",
     *     defaults={ "id" = null, "action" = null },
     *     requirements={ "action" = ".+" })
     * @param Request $request
     * @param string $slug
     * @param string $id
     * @param string $action
     * @return Response
     */
    public function elementAction(Request $request, $slug, $id, $action)
    {
        $application = $this->getApplicationEntity($slug);
        $element = $application->getElements()->matching(Criteria::create()->where(Criteria::expr()->eq('id', $id)))->first();
        if (!$element) {
            throw new NotFoundHttpException();
        }

        if (!$this->filter->prepareFrontend(array($element), true, false)) {
            throw new NotFoundHttpException();
        }
        $handler = $this->filter->getInventory()->getHttpHandler($element);
        if ($handler) {
            return $handler->handleRequest($element, $request);
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * (Re-)render HTML for an element subset, replacing stale partial markup from cache.
     * Only accessed via relative URL from script.
     * See mapbender.application.js _initElements method
     *
     * @Route("/application/{slug}/elements", methods={"GET"})
     * @param Request $request
     * @param $slug
     * @return Response
     */
    public function reloadMarkupAction(Request $request, $slug)
    {
        $application = $this->getApplicationEntity($slug);
        $idsParam = $request->query->get('ids', '');
        $ids = \array_filter(explode(',', $idsParam));

        $criteria = Criteria::create()->where(Criteria::expr()->in('id', $ids));
        $elements = $application->getElements()->matching($criteria)->getValues();
        if (!$elements) {
            throw new NotFoundHttpException();
        }
        $elements = $this->filter->prepareFrontend($elements, false, false);

        $htmlMap = array();
        foreach ($elements as $element) {
            // Map to jQuery-friendly id selectors
            $htmlMap['#' . $element->getId()] = $this->renderer->renderElements(array($element));
        }
        return new JsonResponse($htmlMap);
    }
}
