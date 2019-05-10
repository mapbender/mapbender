<?php

namespace Mapbender\ManagerBundle\Controller;

use Mapbender\ManagerBundle\Extension\Twig\MenuExtension;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manager index controller.
 * Redirects to first menu item.
 * Provides menu via twig "render controller" construct.
 *
 * Copied into Mapbender from FOM v3.0.6.3
 * see https://github.com/mapbender/fom/blob/v3.0.6.3/src/FOM/ManagerBundle/Controller/ManagerController.php
 *
 * @author Christian Wygoda
 * @todo: render menu via twig extension + runtime https://symfony.com/doc/3.4/templating/twig_extension.html#creating-lazy-loaded-twig-extensions
 */
class IndexController extends Controller
{
    /**
     * Simply redirect to the applications list.
     *
     * @ManagerRoute("/", methods={"GET"})
     * @return Response
     */
    public function indexAction()
    {
        /** @var MenuExtension $menuExtension */
        $menuExtension = $this->get('mapbender.twig.manager.menu');
        $defaultRoute = $menuExtension->getDefaultRoute();
        return $this->redirectToRoute($defaultRoute);
    }
}

