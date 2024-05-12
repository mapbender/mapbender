<?php

namespace Mapbender\CoreBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
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
    protected $isDebug;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                protected ApplicationMarkupRenderer $renderer,
                                protected ApplicationMarkupCache $markupCache,
                                EntityManagerInterface $em,
                                $isDebug)
    {
        parent::__construct($yamlRepository, $em);
        $this->isDebug = $isDebug;
    }

    /**
     * Main application controller.
     *
     * @param Request $request
     * @param string $slug Application
     * @return Response
     */
    #[Route(path: '/application/{slug}.{_format}', defaults: ['_format' => 'html'])]
    public function application(Request $request, $slug)
    {
        $appEntity = $this->getApplicationEntity($slug);

        if (!$this->isDebug) {
            return $this->markupCache->getMarkupResponse($request, $appEntity, $this->renderer);
        } else {
            return new Response($this->renderer->renderApplication($appEntity));
        }
    }
}
