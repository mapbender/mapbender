<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class InstanceTunnelController extends AbstractController
{
    protected $isDebug;

    public function __construct(protected InstanceTunnelService $tunnelService,
                                protected EntityManagerInterface $em,
                                $isDebug)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * Get SourceInstances via tunnel
     * @see InstanceTunnelService
     *
     * @param Request $request
     * @param string $instanceId
     * @return Response
     */
    #[Route(path: '/instance/{instanceId}/tunnel')]
    public function instancetunnel(Request $request, $instanceId)
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
     * @param Request $request
     * @param string $instanceId
     * @param string $layerId
     * @return Response
     */
    #[Route(path: 'instance/{instanceId}/tunnel/legend/{layerId}')]
    public function instancetunnellegend(Request $request,$instanceId, $layerId)
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
        $instance = $this->em->getRepository(SourceInstance::class)->find($instanceId);
        if (!$instance) {
            throw new NotFoundHttpException("No such instance");
        }
        if (($layerset = $instance->getLayerset()) && $application = $layerset->getApplication()) {
            $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_VIEW, $application);
        }
        return $this->tunnelService->makeEndpoint($instance);
    }
}
