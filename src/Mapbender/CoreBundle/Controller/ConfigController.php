<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Cache\ApplicationDataService;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConfigController extends YamlApplicationAwareController
{
    /** @var ApplicationDataService|null */
    protected $cacheService;
    protected $enableCache;

    public function __construct(ApplicationYAMLMapper $yamlRepository,
                                protected ConfigService $configService,
                                ApplicationDataService $cacheService,
                                EntityManagerInterface $em,
                                $enableCache)
    {
        parent::__construct($yamlRepository, $em);
        $this->configService = $configService;
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
