<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        protected Environment $templateEngine,
        protected TypeDirectoryService $typeDirectoryService,
        protected bool $showProxiedMetadataUrls
    )
    {
    }

    #[Route(path: '/application/metadata/{instance}/{layerId}', name: 'mapbender_core_application_metadata', methods: ['GET'])]
    public function metadataAction(SourceInstance $instance, int|string $layerId,): Response
    {
        $layerCriteria = Criteria::create()
            ->where(Criteria::expr()->eq('id', $layerId))
        ;
        $startLayerInstance = $instance->getLayers()->matching($layerCriteria)->first() ?: null;
        // NOTE: cannot work for Yaml applications because Yaml-applications don't have source instances in the database
        // @todo: give Yaml applications a proper object repository and make this work
        $source = $instance->getSource();
        $dataSource = $this->typeDirectoryService->getSource($source->getType());
        $template = $dataSource->getMetadataFrontendTemplate();
        if (!$template) {
            throw new NotFoundHttpException();
        }
        $content = $this->templateEngine->render($template, array(
            'instance' => $instance,
            'source' => $source,
            'startLayerInstance' => $startLayerInstance,
            'secureUrls' => !$this->showProxiedMetadataUrls && $dataSource->areMetadataUrlsInternal($instance)
        ));
        return new Response($content);
    }
}
