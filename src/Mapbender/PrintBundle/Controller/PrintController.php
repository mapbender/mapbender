<?php
namespace Mapbender\PrintBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\PrintBundle\Component\PrintService;
use Mapbender\PrintBundle\Component\ImageExportService;

class PrintController extends Controller
{

    /**
     *
     * @Route("/")
     * 
     */
    public function serviceAction()
    {
        $content = $this->get('request')->getContent();
        $data = json_decode($data);

        $filename = 'mapbender_print.pdf';
        if(array_key_exists('file_prefix', $data) &&
            null !== $data['file_prefix'] &&
            '' !== $data['file_prefix']) {
            $filename = $data['file_prefix'];
        }

        $container = $this->container;
        $printservice = new PrintService($container);

        $displayInline = true

        $reponse = new Response($printservice->doPrint($content), 200, array(
            'Content-Type' => $displayInline ? 'application/pdf' : 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename=' . $filename
        );

        return $response;
    }

    /**
     *
     * @Route("/export")
     * 
     */
    public function exportAction()
    {
        $content = $this->get('request')->getContent();
        $container = $this->container;
        $exportservice = new ImageExportService($container);
        $exportservice->export($content);

        return;
    }

}
