<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Mapbender\CoreBundle\Component\Source\Tunnel\Endpoint;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Entity\Application;
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
    public function __construct(
        protected InstanceTunnelService      $tunnelService,
        protected EntityManagerInterface     $em,
        protected bool                       $isDebug,
        private readonly ApplicationResolver $applicationResolver
    )
    {
    }

    /**
     * Get SourceInstances via tunnel
     * @see InstanceTunnelService
     */
    #[Route(path: '/application/{slug}/instance/{instanceId}/tunnel')]
    public function instancetunnel(Request $request, string $slug, string|int $instanceId): Response
    {
        $application = $this->applicationResolver->getApplicationEntity($slug);
        $instanceTunnel = $this->getGrantedTunnelEndpoint($application, $instanceId);
        $requestType = RequestUtil::getGetParamCaseInsensitive($request, 'request', null);
        if (!$requestType) {
            throw new BadRequestHttpException('Missing mandatory parameter `request` in tunnelAction');
        }
        $url = $instanceTunnel->getService()->getInternalUrl($application, $request, false);
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
     */
    #[Route(path: 'application/{slug}/instance/{instanceId}/tunnel/legend/{layerId}')]
    public function instancetunnellegend(Request $request, string $slug, string|int $instanceId): Response
    {
        $application = $this->applicationResolver->getApplicationEntity($slug);
        $instanceTunnel = $this->getGrantedTunnelEndpoint($application, $instanceId);
        $url = $instanceTunnel->getService()->getInternalUrl($application, $request, false);
        if (!$url) {
            throw new NotFoundHttpException();
        }
        if ($this->isDebug && $request->query->has('reveal-internal')) {
            return new Response($url);
        } else {
            return $instanceTunnel->getUrl($url);
        }
    }

    protected function getGrantedTunnelEndpoint(Application $application, string|int $instanceId): Endpoint
    {
        $instance = $application->getSourceInstanceById($instanceId);
        if (!$instance) {
            throw new NotFoundHttpException("No such instance");
        }
        return $this->tunnelService->makeEndpoint($application, $instance);
    }
}
