<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\FrameworkBundle\Component\Renderer\ApplicationMarkupCache;
use Mapbender\FrameworkBundle\Component\Renderer\ApplicationMarkupRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Application controller.
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class ApplicationController extends YamlApplicationAwareController
{
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
        parent::__construct($yamlRepository);
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
}
