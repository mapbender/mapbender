<?php

/**
 * @author Christian Wygoda
 */

namespace Mapbender\WmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ManagerRoute("/repository/wms")
 */
class RepositoryController extends Controller {

    /**
     * @ManagerRoute("/new")
     * @Method({ "GET" })
     * @Template
     */
    public function newAction()
    {
        return array(
            'foo' => "Show WMS import form here and post to parser"
        );
    }
}

