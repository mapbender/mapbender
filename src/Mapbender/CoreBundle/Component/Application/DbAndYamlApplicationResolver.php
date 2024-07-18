<?php

namespace Mapbender\CoreBundle\Component\Application;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DbAndYamlApplicationResolver implements ApplicationResolver
{

    public function __construct(
        protected ApplicationYAMLMapper         $yamlRepository,
        protected EntityManagerInterface        $em,
        protected AuthorizationCheckerInterface $authorizationChecker,
    )
    {
    }


    public function getApplicationEntity(string $slug): Application
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

    protected function denyAccessUnlessGranted(string $attribute, mixed $subject): void
    {
        if (!$this->authorizationChecker->isGranted($attribute, $subject)) {
            $exception = new AccessDeniedException("Access Denied.");
            $exception->setAttributes([$attribute]);
            $exception->setSubject($subject);

            throw $exception;
        }
    }

}
