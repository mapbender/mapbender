<?php
namespace Mapbender\PrintBundle\Controller;

use Mapbender\PrintBundle\Element\Token\SignerToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $data          = $this->decodeToken();
        $fileName      = empty($data['file_prefix']) ? 'mapbender_print.pdf' : $data['file_prefix'];
        $displayInline = true;
        $r             = null;

        $queueManager = $this->get('mapbender.print.queue_manager');
        $queue        = $queueManager->add($data);
        $uri          = $queueManager->getPdfUri($queue);
        $r            = new Response( $this->serialize(array('link' => $this->get('templating.helper.assets')->getUrl($uri),
                                                             'id'   => $queue->getId()))
        );

        return $r;
    }

    /**
     * @Route("/queues")
     * @Template()
     */
    public function queuesAction()
    {
        return array();
    }

    /**
     * Secured element API method
     *
     * @Route("/queuelist")
     */
    public function queueListAction()
    {
        $token   = $this->decodeToken();
        $type    = $token['request']['type'];
        $userId  = $token['userId'];
        $manager = $this->get('mapbender.print.queue_manager');
        $user    = $manager->getUserById($userId);
        $queues  = array();

        if ($type == 'own') {
            if ($user) {
                $queues = $manager->getUserQueueInfos($user->getId());
            }
        } else {
            if ($user && $user->isAdmin()) {
                $queues = $manager->getUserQueueInfos();
            }
        }

        return new JsonResponse(array('data' => $queues));
    }

    /**
     * Remove queue
     *
     * @Route("/remove")
     */
    public function removeAction()
    {
        $token   = $this->decodeToken();
        $manager = $this->get('mapbender.print.queue_manager');
        $queue   = $manager->find($token['request']['id']);
        return new JsonResponse($manager->remove($queue));
    }

    /**
     * @Route("/test")
     */
    public function testAction()
    {
        return new JsonResponse($this->get('mapbender.print.queue_manager')->getUserQueueInfos());
    }

    /**
     * @Route("/export")
     */
    public function exportAction()
    {
        $imageExportService = new ImageExportService($this->container);
        return $imageExportService->export($this->get('request')->getContent());
    }

    /**
     * @return array
     */
    protected function decodeToken()
    {
        /** @var SignerToken $token */
        $token = $this->container->get('signer')->load($this->getRequest()->getContent(),'Mapbender\PrintBundle\Element\Token\SignerToken');
        return $token && $token instanceof SignerToken? $token->getData(): null;
    }

    /**
     * Serialize as json
     *
     * @param $data array(userId,element,data)
     * @return string|\Symfony\Component\Serializer\Encoder\scalar
     */
    public function serialize($data)
    {
        $this->serializer = $this->serializer ? $this->serializer : new Serializer(array(new GetSetMethodNormalizer()),array(new JsonEncode()));
        return $this->serializer->serialize($data, 'json');
    }
}
