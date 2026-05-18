<?php


namespace Mapbender\CoreBundle\Controller;


use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Used only by Layertree.
 * Not a layertree http handler action because
 * a) Metadata availability is predetermined per instance when generating config to skip yaml apps / non-wms instances
 * b) we do not know / do not care which layertree element initiates the request (no element id in url)
 *
 * @see \Mapbender\WmsBundle\Component\Presenter\WmsSourceInstanceConfigGenerator::getMetadataUrl()
 */
class SourceMetaDataController
{
    public function __construct(
        protected Environment                $templateEngine,
        protected TypeDirectoryService       $typeDirectoryService,
        protected bool                       $showProxiedServiceUrls,
        private readonly ApplicationResolver $applicationResolver,
        private Security                     $security,
    )
    {
    }

    #[Route(path: '/application/{slug}/metadata/{instanceId}/{layerId}', name: 'mapbender_core_application_metadata', methods: ['GET'])]
    public function metadataAction(int|string $slug, int|string $instanceId, int|string $layerId): Response
    {
        $application = $this->applicationResolver->getApplicationEntity($slug);
        if (!$this->security->isGranted(ResourceDomainApplication::ACTION_VIEW, $application)) {
            return new Response(null, Response::HTTP_FORBIDDEN);
        }

        $instance = $application->getSourceInstanceById($instanceId);
        if (!$instance) {
            return new Response('Invalid instance id', Response::HTTP_NOT_FOUND);
        }
        $startLayerInstance = $instance->getLayerById($layerId);
        if (!$startLayerInstance) {
            return new Response('Invalid instance layer id', Response::HTTP_NOT_FOUND);
        }

        $source = $instance->getSource();
        $dataSource = $this->typeDirectoryService->getSource($source->getType());
        $loader = $dataSource->getLoader();
        if (!$loader->hasPersistedMetadata($application, $source)) {
            $source = $loader->loadSource($source);
        }
        $template = $dataSource->getMetadataFrontendTemplate();
        if (!$template) {
            throw new NotFoundHttpException();
        }
        $content = $this->templateEngine->render($template, array(
            'instance' => $instance,
            'source' => $source,
            'startLayerInstance' => $startLayerInstance,
            'secureUrls' => !$this->showProxiedServiceUrls && $dataSource->areServiceUrlsInternal($instance)
        ));
        return new Response($content);
    }
}
