<?php


namespace FOM\UserBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class UserControllerBase extends AbstractController
{
    public function __construct(protected string $userEntityClass, protected ManagerRegistry $doctrine)
    {
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass($this->userEntityClass);
    }

    protected function getUserRepository(): ObjectRepository
    {
        return $this->doctrine->getRepository($this->userEntityClass);
    }
}
