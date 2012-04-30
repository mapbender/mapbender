<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Mapbender\CoreBundle\Entity\User;
use Mapbender\CoreBundle\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use Acme\HelloBundle\Mailer;

/**
 * User controller.
 *
 * @author Christian Wygoda
 * @author Paul Schmidt
 */
class UserController extends Controller {
    protected $em;

    /**
     * User login
     *
     * @Route("/user/login")
     * @Template()
     * @Method("GET")
     */
    public function loginAction() {
        $request = $this->get('request');
        if($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }

        return array(
            'last_username' => $request->getSession()->get(SecurityContext::LAST_USERNAME),
            'error' => $error,
        );
    }

    /**
     * @Route("/user/login/check")
     * @Method("GET")
     */
    public function loginCheckAction() {
        //Don't worry, this is actually intercepted by the security layer.
    }

    /**
     * @Route("/user/logout")
     */
    public function logoutAction() {
        //Don't worry, this is actually intercepted by the security layer.
    }

    /**
     * @Route("/user/register")
     * @Template
     */
    public function registerAction() {
        $request = $this->get('request');
        $user = new User();
        $form = $this->createFormBuilder($user)
                ->add('username', 'text')
                ->add('password', 'password')
                ->add('email', 'text')
                ->add('firstName', 'text')
                ->add('lastName', 'text')
                ->add('captcha', 'captcha', array( 'width' => 200, 'height' => 50, 'length' => 6, ))
                ->getForm();

        if($request->getMethod() === 'POST') {
            $form->bindRequest($request);
            if($form->isValid()) {
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($user);
                $em->flush();
                //email senden
                $mailTo = $user->getEmail();
                $mailFrom = "info@wheregroup.com";
                $mailer = $this->get('mailer');
//                $sender = $this->get('email_sender');
                $message = \Swift_Message::newInstance()
            ->setSubject('Ein WMS2Go-Job wurde erfolgreich erstellt')
//          ->setFrom(array($sender['email'] => $sender['name']))
                        ->setFrom(array("paul.schmidt@wheregroup.com" => "Paul Sch"))
            ->setTo($user->getEmail())
//          ->setBody($this->renderEmail('BawWms2GoBundle:Job:email_success.email.twig',
//              array('job' => $event->getJob())));
                         ->setBody("bla bla");
        $mailer->send($message);
                return $this->redirect($this->generateUrl('mapbender_core_user_login'));
            }
        }

        return $this->render('MapbenderCoreBundle:User:register.html.twig',
                array('form' => $form->createView()));
    }

    /**
     * @Route("/user/")
     * @Method("GET")
     * @Template()
     * @ParamConverter("userList",class="Mapbender\CoreBundle\Entity\User")
     */
    public function indexAction(array $userList) {
        return array(
            "userList" => $userList
        );
    }

    /**
     * @Route("/user/create")
     * @Method("GET")
     * @Template()
     */
    public function createAction() {
        $form = $this->get("form.factory")->create(
                new UserType(),
                new User()
        );


        return array(
            "form" => $form->createView()
        );
    }

    /**
     * @Route("/user/")
     * @Method("POST")
     */
    public function addAction() {
        $user = new User();

        $form = $this->get("form.factory")->create(
                new UserType(),
                $user
        );

        $request = $this->get("request");

        $form->bindRequest($request);

        if($form->isValid()) {

      $user->setRoles(array("ROLE_USER"));
      $factory  = $this->container->get('security.encoder_factory');
      $encoder = $factory->getEncoder($user);
      $password = $encoder->encodePassword($user->getPassword(),$user->getSalt());
      $user->setPassword($password);
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($user);
            $em->flush();
            return $this->redirect($this->generateUrl("mapbender_core_user_index"));
        } else {
            return $this->render(
                "MapbenderCoreBundle:User:create.html.twig",
                array("form" => $form->createView())
            );
        }
    }


    /**
     * @Route("/user/{userId}")
     * @Method("GET")
     * @Template()
     */
    public function editAction(User $user) {
        $form = $this->get("form.factory")->create(
                new UserType(),
                $user
        );

        return array(
            "form" => $form->createView(),
            "user" => $user
        );
    }

    /**
     * @Route("/user/{userId}/delete")
     * @Method("POST")
     */
    public function deleteAction(User $user) {
        $em = $this->getDoctrine()->getEntityManager();
        try {
            $em->remove($user);
            $em->flush();
        } catch(\Exception $E) {
            $this->get("logger")->info("Could not delete user. ".$E->getMessage());
            $this->get("session")->setFlash("error","Could not delete user.");
            return $this->redirect($this->generateUrl("mapbender_core_user_index"));
        }

        $this->get("session")->setFlash("info","Successfully deleted.");
        return $this->redirect($this->generateUrl("mapbender_core_user_index"));
    }

    /**
     * @Route("/user/{userId}/delete")
     * @Method("GET")
     * @Template()
     */
    public function confirmdeleteAction(User $user) {
        return array(
            "user" => $user
        );
    }

    /**
     * @Route("/user/{userId}")
     * @Method("POST")
     */
    public function saveAction(User $user) {
        $form = $this->get("form.factory")->create(
                new UserType(),
                $user
        );

        $request = $this->get("request");

        $form->bindRequest($request);

        if($form->isValid()) {
      $factory  = $this->container->get('security.encoder_factory');
      $encoder = $factory->getEncoder($user);
      $password = $encoder->encodePassword($user->getPassword(),$user->getSalt());
      $user->setPassword($password);
            try {
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($user);
                $em->flush();
            } catch(\Exception $E) {
                $this->get("logger")->error("Could not save user. ".$E->getMessage());
                $this->get("session")->setFlash("error","Could not save user");
                return $this->redirect($this->generateUrl("mapbender_core_user_edit",array("mdId" => $user->getId())));
            }
            return $this->redirect($this->generateUrl("mapbender_core_user_index"));
        } else {
            return $this->render(
                "MapbenderCoreBundle:User:edit.html.twig",
                array("form" => $form->createView(),
                    "user" => $user)
            );
        }
    }
    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = NULL) {
        parent::setContainer($container);
        if($this->container === NULL) {
            throw \Exception('Mapbender\CoreBundle\Controller\UserController requires the container to be set.');
        }

        $this->em = $this->get('doctrine.orm.default_entity_manager');
    }


    /**
     * @Route("/")
     * @Secure("ROLE_USER")
     * @Template()
     */
    public function profileAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        return array(
            'user' => $user,
        );
    }
}
