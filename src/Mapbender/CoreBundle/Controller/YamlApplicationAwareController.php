<?php


namespace Mapbender\CoreBundle\Controller;


use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class YamlApplicationAwareController extends AbstractController
{
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;

    public function __construct(ApplicationYAMLMapper $yamlRepository)
    {
        $this->yamlRepository = $yamlRepository;
    }

    /**
     * @param string $slug
     * @return Application
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    protected function getApplicationEntity($slug)
    {
        /** @var Application|null $application */
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        $application = $application ?: $this->yamlRepository->getApplication($slug);
        if (!$application) {
            throw new NotFoundHttpException();
        }
        $this->denyAccessUnlessGranted('VIEW', $application);
        return $application;
    }
}
