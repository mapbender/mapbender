<?php
namespace Mapbender\ManagerBundle\Listener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Session\Session;
use Mapbender\CoreBundle\Entity\User;

/**
 * LoginListener.
 * 
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class LoginListener {

	/** @var Symfony\Component\HttpFoundation\Session */
    private $session;
    /** @var Symfony\Component\Security\Core\SecurityContext */
    private $context;

	/** @var Doctrine\ORM\EntityManager */
	private $doctrine;

	/**
	 * Constructor
	 *
	 * @param SecurityContext $context
	 * @param Doctrine $doctrine
	 */
	public function __construct(SecurityContext $context, Registry $doctrine, Session $session) {
		$this->context = $context;
        $this->session = $session;
		$this->doctrine = $doctrine;
	}

	/**
	 * .
	 *
	 * @param Event $event
	 */
	public function onLogin(Event $event) {
        if($this->context->getToken()!= null) {
            if($this->context->getToken()->getUser() instanceof User){
                $user = $this->context->getToken()->getUser();
                if($user->getRegistrationToken() || $user->getResetToken()){
                    $user = $this->doctrine->getRepository("MapbenderCoreBundle:User")
                            ->findOneById($user->getId());
                    $em = $this->doctrine->getEntityManager();
                    $user->setRegistrationToken(null);
                    $user->setResetToken(null);
                    $user->setResetTime(null);
                    $em->persist($user);
                    $em->flush();
                }
            }
        }
	}

}

