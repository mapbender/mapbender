<?php
namespace Mapbender\PrintBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\PrintBundle\Component\ImageExportService;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class PrintController extends Controller
{
    /** @var  Serializer */
    protected $serializer;

    /**
     * @Route("/")
     */
    public function serviceAction()
    {
        $data          = $this->getDecodedRequest();
        $fileName      = empty($data['file_prefix']) ? 'mapbender_print.pdf' : $data['file_prefix'];
        $displayInline = true;
        $r             = null;

        switch (empty($data['renderMode']) ? 'direct' : $data['renderMode']) {
            case 'direct':
                $pdfContent = $this->get('mapbender.print.engine')->doPrint($data);
                $r          = new Response($pdfContent,
                    200,
                    array('Content-Type'        => $displayInline ? 'application/pdf' : 'application/octet-stream',
                          'Content-Disposition' => 'attachment; filename=' . $fileName)
                );
                break;
            case 'queued':
                $printQueue = $this->get('mapbender.print.queue_manager')->add($data);
                $r          = new Response($this->serialize($printQueue->getToken()));
                break;
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

    /**
     * Serialize as json
     *
     * @param $data
     * @return string|\Symfony\Component\Serializer\Encoder\scalar
     */
    public function serialize($data)
    {
        $this->serializer = $this->serializer ? $this->serializer : new Serializer(array(new GetSetMethodNormalizer()),array(new JsonEncode()));
        return $this->serializer->serialize($data, 'json');
    }
}
