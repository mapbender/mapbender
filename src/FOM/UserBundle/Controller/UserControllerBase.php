<?php


namespace FOM\UserBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class UserControllerBase extends Controller
{
    protected $userEntityClass;

    public function __construct($userEntityClass)
    {
        $this->userEntityClass = $userEntityClass;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManagerForClass($this->userEntityClass);
        return $em;
    }

    /**
     * @return \Doctrine\Persistence\ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getDoctrine()->getRepository($this->userEntityClass);
    }
}
