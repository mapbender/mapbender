<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class InstanceTunnelController extends AbstractController
{
    /** @var InstanceTunnelService */
    protected $tunnelService;
    protected $isDebug;

    public function __construct(InstanceTunnelService $tunnelService,
                                $isDebug)
    {
        $this->tunnelService = $tunnelService;
        $this->isDebug = $isDebug;
    }

    /**
     * Get SourceInstances via tunnel
     * @see InstanceTunnelService
     *
     * @Route("/instance/{instanceId}/tunnel")
     * @param Request $request
     * @param string $instanceId
     * @return Response
     */
    public function instancetunnelAction(Request $request, $instanceId)
    {
        $instanceTunnel = $this->getGrantedTunnelEndpoint($instanceId);
        $requestType = RequestUtil::getGetParamCaseInsensitive($request, 'request', null);
        if (!$requestType) {
            throw new BadRequestHttpException('Missing mandatory parameter `request` in tunnelAction');
        }
        $url = $instanceTunnel->getService()->getInternalUrl($request, false);
        if ($this->isDebug && $request->query->has('reveal-internal')) {
            return new Response($url);
        }

        if (!$url) {
            throw new NotFoundHttpException('Operation "' . $requestType . '" is not supported by "tunnelAction".');
        }

        return $instanceTunnel->getUrl($url);
    }

    /**
     * Get a layer's legend image via tunnel
     * @see InstanceTunnelService
     *
     * @Route("instance/{instanceId}/tunnel/legend/{layerId}")
     * @param Request $request
     * @param string $instanceId
     * @param string $layerId
     * @return Response
     */
    public function instancetunnellegendAction(Request $request,$instanceId, $layerId)
    {
        $instanceTunnel = $this->getGrantedTunnelEndpoint($instanceId);
        $url = $instanceTunnel->getService()->getInternalUrl($request, false);
        if (!$url) {
            throw new NotFoundHttpException();
        }
        if ($this->isDebug && $request->query->has('reveal-internal')) {
            return new Response($url);
        } else {
            return $instanceTunnel->getUrl($url);
        }
    }

    /**
     * @param string $instanceId
     * @return \Mapbender\CoreBundle\Component\Source\Tunnel\Endpoint
     */
    protected function getGrantedTunnelEndpoint($instanceId)
    {
        /** @var SourceInstance|null $instance */
        $instance = $this->getDoctrine()
            ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($instanceId);
        if (!$instance) {
            throw new NotFoundHttpException("No such instance");
        }
        if (($layerset = $instance->getLayerset()) && $application = $layerset->getApplication()) {
            if (!$this->isGranted('VIEW', $application)) {
                $this->denyAccessUnlessGranted('VIEW', $application);
            }
        }
        return $this->tunnelService->makeEndpoint($instance);
    }
}
