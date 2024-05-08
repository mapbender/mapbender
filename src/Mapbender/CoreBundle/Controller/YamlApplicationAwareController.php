<?php


namespace Mapbender\CoreBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class YamlApplicationAwareController extends AbstractController
{
    public function __construct(protected ApplicationYAMLMapper $yamlRepository, protected EntityManagerInterface $em)
    {
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
        $application = $this->em->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        $application = $application ?: $this->yamlRepository->getApplication($slug);
        if (!$application) {
            throw new NotFoundHttpException();
        }
        $this->denyAccessUnlessGranted(ResourceDomainApplication::ACTION_VIEW, $application);
        return $application;
    }
}
