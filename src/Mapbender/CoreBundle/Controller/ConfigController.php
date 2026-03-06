<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Mapbender\CoreBundle\Component\Cache\ApplicationDataService;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConfigController extends AbstractController
{
    /** @var ApplicationDataService|null */
    protected $cacheService;

    public function __construct(protected ApplicationResolver $applicationResolver,
                                protected ConfigService       $configService,
                                ApplicationDataService        $cacheService,
                                                              $enableCache)
    {
        $this->cacheService = ($enableCache ? $cacheService : null) ?: null;
    }

    /**
     *
     * @param string $slug
     * @return Response
     */
    #[Route(path: '/application/{slug}/config', name: 'mapbender_core_application_configuration')]
    public function configuration($slug)
    {
        $applicationEntity = $this->applicationResolver->getApplicationEntity($slug);
        $cacheKeyPath = array('config.json');

        if ($this->cacheService) {
            $response = $this->cacheService->getResponse($applicationEntity, $cacheKeyPath, 'application/json');
        } else {
            $response = false;
        }
        if (!$response) {
            $freshConfig = $this->configService->getConfiguration($applicationEntity);
            $response = new JsonResponse($freshConfig);
            $this->cacheService?->putValue($applicationEntity, $cacheKeyPath, $response->getContent());
        }
        return $response;
    }
}
