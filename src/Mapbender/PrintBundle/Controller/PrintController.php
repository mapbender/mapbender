<?php
namespace Mapbender\PrintBundle\Controller;

use Mapbender\PrintBundle\Element\Token\SignerToken;
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
        /** @var $token SignerToken */
        $data          = $this->decodeTransitRequest();
        $fileName      = empty($data['file_prefix']) ? 'mapbender_print.pdf' : $data['file_prefix'];
        $displayInline = true;
        $r             = null;

        switch (empty($data['renderMode']) ? 'direct' : $data['renderMode']) {
            case 'direct':
                $r = new Response($this->get('mapbender.print.engine')->doPrint($data),
                    200,
                    array('Content-Type' => $displayInline ? 'application/pdf' : 'application/octet-stream',
                          'Content-Disposition' => 'attachment; filename=' . $fileName)
                );
                break;
            case 'queued':
                $r = new Response($this->serialize($this->get('mapbender.print.queue_manager')->add($data)->getToken()));
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
        $service->export(json_decode($this->getRequest()->getContent()));
    }

    /**
     * @return array
     */
    protected function decodeTransitRequest()
    {
        /** @var SignerToken $token */
        $token = $this->container->get('signer')->load($this->getRequest()->getContent(),'Mapbender\PrintBundle\Element\Token\SignerToken');
        return $token && $token instanceof SignerToken? $token->getData(): null;
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
