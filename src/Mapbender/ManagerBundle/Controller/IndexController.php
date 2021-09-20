<?php

namespace Mapbender\ManagerBundle\Controller;

use Mapbender\ManagerBundle\Extension\Twig\MenuExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manager index controller.
 * Redirects to first menu item.
 *
 * Originally copied into Mapbender from FOM v3.0.6.3
 * see https://github.com/mapbender/fom/blob/v3.0.6.3/src/FOM/ManagerBundle/Controller/ManagerController.php
 *
 * @author Christian Wygoda
  */
class IndexController extends AbstractController
{
    /** @var string */
    protected $defaultRoute;

    public function __construct(MenuExtension $menuExtension)
    {
        $this->defaultRoute = $menuExtension->getDefaultRoute();
    }

    /**
     * Simply redirect to the applications list.
     *
     * @ManagerRoute("/", methods={"GET"})
     * @return Response
     */
    public function indexAction()
    {
        return $this->redirectToRoute($this->defaultRoute);
    }
}

