<?php

namespace Mapbender\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Translation controller.
 *
 * @author Paul Schmidt
 *
 */
class TranslationController extends Controller
{
    /**
     * @Route("/trans")
     * @Method({"POST"})
     */
    public function transAction()
    {
        $tr         = $this->get('translator');
        $request    = $this->get('request');
        $templating = $this->get("templating");
        $data       = array();

        foreach ($request->request->all() as $k => $v) {
            $par_vals = explode("|", $v);
            if (count($par_vals) == 1) {
                $data[ $k ] = $tr->trans($v);
            } else {
                if (count($par_vals) > 1) {
                    if ($par_vals[0] == "twig") {
                        $content    = $templating->render($par_vals[1], array());
                        $data[ $k ] = $content;
                    }
                }
            }
        }

        return new Response(json_encode($data), 200, array(
            'Content-Type' => 'application/json'
        ));
    }
}
