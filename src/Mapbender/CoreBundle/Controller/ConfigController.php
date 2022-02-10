<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends YamlApplicationAwareController
{
    /** @var ConfigService */
    protected $configService;
    protected $enableCache;
    protected $forcedMapEngine;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                ConfigService $configService,
                                $forcedMapEngine,
                                $enableCache)
    {
        parent::__construct($yamlRepository);
        $this->configService = $configService;
        $this->forcedMapEngine = $forcedMapEngine;
        $this->enableCache = $enableCache;
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
        if ($this->forcedMapEngine) {
            $applicationEntity->setMapEngineCode($this->forcedMapEngine);
        }
        $cacheService = $this->configService->getCacheService();
        $cacheKeyPath = array('config.json');

        if ($this->enableCache) {
            $response = $cacheService->getResponse($applicationEntity, $cacheKeyPath, 'application/json');
        } else {
            $response = false;
        }
        if (!$this->enableCache || !$response) {
            $freshConfig = $this->configService->getConfiguration($applicationEntity);
            $response = new JsonResponse($freshConfig);
            if ($this->enableCache) {
                $cacheService->putValue($applicationEntity, $cacheKeyPath, $response->getContent());
            }
        }
        return $response;
    }
}
