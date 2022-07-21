<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\FrameworkBundle\Component\Renderer\ApplicationMarkupCache;
use Mapbender\FrameworkBundle\Component\Renderer\ApplicationMarkupRenderer;
use Mapbender\ManagerBundle\Controller\ApplicationControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Application controller.
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class ApplicationController extends ApplicationControllerBase
{
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;
    /** @var ApplicationMarkupRenderer */
    protected $renderer;
    /** @var ApplicationMarkupCache */
    protected $markupCache;
    protected $isDebug;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                ApplicationMarkupRenderer $renderer,
                                ApplicationMarkupCache $markupCache,
                                $isDebug)
    {
        $this->yamlRepository = $yamlRepository;
        $this->renderer = $renderer;
        $this->markupCache = $markupCache;
        $this->isDebug = $isDebug;
    }

    /**
     * Main application controller.
     *
     * @Route("/application/{slug}.{_format}", defaults={ "_format" = "html" })
     * @param Request $request
     * @param string $slug Application
     * @return Response
     */
    public function applicationAction(Request $request, $slug)
    {
        $appEntity = $this->getApplicationEntity($slug);

        if (!$this->isDebug) {
            return $this->markupCache->getMarkupResponse($request, $appEntity, $this->renderer);
        } else {
            return new Response($this->renderer->renderApplication($appEntity));
        }
    }

    /**
     * @param string $slug
     * @return Application
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    private function getApplicationEntity($slug)
    {
        /** @var Application|null $application */
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        $application = $application ?: $this->yamlRepository->getApplication($slug);
        if (!$application) {
            throw new NotFoundHttpException();
        }
        $this->denyAccessUnlessGranted('VIEW', $application);
        return $application;
    }
}
