<?php


namespace FOM\UserBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Component\UserHelperService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

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

    /**
     * @return UserHelperService
     */
    protected function getUserHelper()
    {
        /** @var UserHelperService $service */
        $service = $this->get('fom.user_helper.service');
        return $service;
    }

    /**
     * @return MutableAclProviderInterface
     */
    protected function getAclProvider()
    {
        /** @var MutableAclProviderInterface $service */
        $service = $this->get('security.acl.provider');
        return $service;
    }

    protected function translate($x)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->container->get('translator');
        return $translator->trans($x);
    }
}
