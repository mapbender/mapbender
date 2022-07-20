<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Used only by Layertree.
 * Not a layertree http handler action because
 * a) Metadata availability is predetermined per instance when generating config to skip yaml apps / non-wms instances
 * b) we do not know / do not care which layertree element initiates the request (no element id in url)
 *
 * @see \Mapbender\WmsBundle\Component\Presenter\WmsSourceService::getMetadataUrl()
 */
class SourceMetaDataController
{
    protected $templateEngine;

    public function __construct(\Twig\Environment $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * @Route("/application/metadata/{instance}/{layerId}",
     *     name="mapbender_core_application_metadata",
     *     methods={"GET"})
     * @param SourceInstance $instance
     * @param string $layerId
     * @return Response
     */
    public function metadataAction(SourceInstance $instance, $layerId)
    {
        $layerCriteria = Criteria::create()
            ->where(Criteria::expr()->eq('id', $layerId))
        ;
        $startLayerInstance = $instance->getLayers()->matching($layerCriteria)->first() ?: null;
        // NOTE: cannot work for Yaml applications because Yaml-applications don't have source instances in the database
        // @todo: give Yaml applications a proper object repository and make this work
        $template = $instance->getSource()->getViewTemplate(true);
        if (!$template) {
            throw new NotFoundHttpException();
        }
        $content = $this->templateEngine->render($template, array(
            'instance' => $instance,
            'source' => $instance->getSource(),
            'startLayerInstance' => $startLayerInstance,
        ));
        return new Response($content);
    }
}
