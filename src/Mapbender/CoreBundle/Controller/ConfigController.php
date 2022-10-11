<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Cache\ApplicationDataService;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends YamlApplicationAwareController
{
    /** @var ConfigService */
    protected $configService;
    /** @var ApplicationDataService|null */
    protected $cacheService;
    protected $enableCache;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                ConfigService $configService,
                                ApplicationDataService $cacheService,
                                $enableCache)
    {
        parent::__construct($yamlRepository);
        $this->configService = $configService;
        $this->cacheService = ($enableCache ? $cacheService : null) ?: null;
    }

    /**
     *
     * @Route("/application/{slug}/config",
     *     name="mapbender_core_application_configuration")
     * @param string $slug
     * @return Response
     */
    public function configurationAction($slug)
    {
        $applicationEntity = $this->getApplicationEntity($slug);
        $cacheKeyPath = array('config.json');

        if ($this->cacheService) {
            $response = $this->cacheService->getResponse($applicationEntity, $cacheKeyPath, 'application/json');
        } else {
            $response = false;
        }
        if (!$response) {
            $freshConfig = $this->configService->getConfiguration($applicationEntity);
            $response = new JsonResponse($freshConfig);
            if ($this->cacheService) {
                $this->cacheService->putValue($applicationEntity, $cacheKeyPath, $response->getContent());
            }
        }
        return $response;
    }
}
