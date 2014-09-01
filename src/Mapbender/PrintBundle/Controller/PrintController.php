<?php
namespace Mapbender\PrintBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\PrintBundle\Component\PrintService;
use Mapbender\PrintBundle\Component\ImageExportService;

class PrintController extends Controller
{
    /**
     * @Route("/")
     */
    public function serviceAction()
    {
        // checkout if mode=direct|queue
        $data          = $this->getDecodedRequest();
        $fileName      = empty($data['file_prefix']) ? 'mapbender_print.pdf' : $data['file_prefix'];
        $service       = new PrintService($this->container);
        $displayInline = true;
        $r             = null;

        if (!empty($data['mode']) && $data['mode'] == 'queued') {
            $r = new JsonResponse($this->get('mapbender.print.queue_manager')->add($data));
        } else {
            $r = new Response($service->doPrint($data), 200,
                array('Content-Type'        => $displayInline ? 'application/pdf' : 'application/octet-stream',
                      'Content-Disposition' => 'attachment; filename=' . $fileName)
            );
        }

        return $r;
    }

    /**
     * @Route("/export")
     */
    public function exportAction()
    {
        $service = new ImageExportService($this->container);
        $service->export($this->getDecodedRequest());
    }

    /**
     * @return array
     */
    protected function getDecodedRequest()
    {
        return json_decode($this->get('request')->getContent(), true);
    }
}
