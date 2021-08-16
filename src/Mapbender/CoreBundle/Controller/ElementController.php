<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ElementController extends YamlApplicationAwareController
{
    /** @var ElementFilter */
    protected $filter;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                ElementFilter $filter)
    {
        parent::__construct($yamlRepository);
        $this->filter = $filter;
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
}
