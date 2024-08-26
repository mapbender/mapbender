<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Mapbender\FrameworkBundle\Component\Renderer\ElementMarkupRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ElementController extends AbstractController
{
    public function __construct(protected ApplicationResolver   $applicationResolver,
                                protected ElementFilter         $filter,
                                protected ElementMarkupRenderer $renderer,
    )
    {
    }

    /**
     * Element action controller.
     *
     * Passes the request to
     * the element's handleHttpRequest.
     * @param Request $request
     * @param string $slug
     * @param string $id
     * @param string $action
     * @return Response
     */
    #[Route(path: '/application/{slug}/element/{id}/{action}', name: 'mapbender_core_application_element', requirements: ['action' => '.+'], defaults: ['id' => null, 'action' => null])]
    public function element(Request $request, $slug, $id, string $action)
    {
        $application = $this->applicationResolver->getApplicationEntity($slug);
        $id = intval($id);
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
     * @param Request $request
     * @param $slug
     * @return Response
     */
    #[Route(path: '/application/{slug}/elements', methods: ['GET'])]
    public function reloadMarkup(Request $request, $slug)
    {
        $application = $this->applicationResolver->getApplicationEntity($slug);
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
