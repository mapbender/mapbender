<?php

namespace Mapbender\CoreBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
/**
 * Translation controller.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 *
 */
class TranslationController extends Controller {
     /**
     * @Route("/transtext")
     * @Method({"POST"})
     */
    public function transtextAction() {
        $tr = $this->get('translator');
        $request = $this->get('request');
        $paramspost = $request->request->all();
        $data = array();
        foreach ($paramspost as $k => $v) {
            $par_vals = explode("|", $v);
            if(count($par_vals) == 1){
                $data[$k] = $tr->trans($v);
            } else if(count($par_vals) > 1){
                if($par_vals[0]== "twig"){
                    $templating = $this->get("templating");
                    $content = $templating->render($par_vals[1],array());
                    $data[$k] = $content;
                }
            }
        }
        $response = new Response();
        $response->setContent(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
