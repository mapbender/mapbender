<?php


namespace Mapbender\CoreBundle\Controller;


use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Entity\SourceItem;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
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
    public function metadataAction(int|string $slug, int|string $instanceId, int|string $layerId, Request $request): Response
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
        $source = $this->resolveSource($application, $source, $request, $dataSource, $startLayerInstance);
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

    public function resolveSource(Application $application, Source $source, Request $request, DataSource $dataSource, SourceInstanceItem $startLayerInstance): Source
    {
        $loader = $dataSource->getLoader();
        // metadata persisted, no need to load it from server
        if ($loader->hasPersistedMetadata($application, $source)) {
            return $source;
        }

        $source = $loader->loadSource($source);
        $layerName = $request->get('layerName');

        $this->resolveLayerRecursive($source, $layerName, $startLayerInstance);
        return $source;
    }

    /**
     * replace minimal source items (read from yaml) by metadata-enriched source items (loaded from server). Matching by name.
     */
    protected function resolveLayerRecursive(Source $source, ?string $layerName, SourceInstanceItem $layerInstance): void
    {
        if (!$layerName) {
            if (method_exists($source, 'getRootLayer')) {
                // keep (potentially overridden) title from yaml
                $layerInstance->setTitle($layerInstance->getSourceItem()->getTitle());
                $layerInstance->setSourceItem($source->getRootLayer());
            }
        } else foreach ($source->getLayers() as $layer) {
            /** @var SourceItem $layer */
            if (method_exists($layer, 'getName') && $layer->getName() === $layerName) {
                $layerInstance->setTitle($layerInstance->getSourceItem()->getTitle());
                $layerInstance->setSourceItem($layer);
            }
        }

        if (method_exists($layerInstance, 'getSublayer')) {

            foreach ($layerInstance->getSublayer() as $sublayer) {
                /** @var $sublayer SourceInstanceItem */
                $sourceItem = $sublayer->getSourceItem();
                $name = method_exists($sourceItem, 'getName') ? $sourceItem->getName() : $sourceItem->getId();
                $this->resolveLayerRecursive($source, $name, $sublayer);
            }
        }
    }
}
