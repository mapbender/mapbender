<?php

namespace FOM\UserBundle\Controller;

use FOM\UserBundle\Entity\Group;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Self registration controller.
 *
 * @author Christian Wygoda
 * @author Paul Schmidt
 */

class RegistrationController extends UserControllerBase
{
    /**
     * Check if self registration is allowed.
     *
     * setContainer is called after controller creation is used to deny access to controller if self registration has
     * been disabled.
     * @param ContainerInterface $container
     * @throws AccessDeniedHttpException
     */
    public function setContainer(ContainerInterface $container = NULL)
    {
        parent::setContainer($container);
        if (!$this->getEmailFromAdress()) {
            $this->debug404("Sender mail not configured. See UserBundle/CONFIGURATION.md");
        }
        if (!$this->container->getParameter('fom_user.selfregister')) {
            $this->debug404("Registration disabled by configuration");
        }
    }

    /**
     * Registration step 3: Show instruction page that email has been sent
     *
     * @Route("/user/registration/send", methods={"GET"})
     * @return Response
     */
    public function sendAction()
    {
        return $this->render('@FOMUser/Registration/send.html.twig');
    }

    /**
     * Registration step 1: Registration form
     *
     * @Route("/user/registration", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function formAction(Request $request)
    {
        $userClass = $this->getUserEntityClass();
        /** @var User $user */
        $user = new $userClass();
        $form = $this->createForm('FOM\UserBundle\Form\Type\UserRegistrationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $helperService = $this->getUserHelper();
            $helperService->setPassword($user, $user->getPassword());

            $user->setRegistrationToken(hash("sha1",rand()));
            $user->setRegistrationTime(new \DateTime());

            $groupRepository = $this->getDoctrine()->getRepository('FOMUserBundle:Group');
            foreach($this->container->getParameter('fom_user.self_registration_groups') as $groupTitle) {
                /** @var Group|null $group */
                $group = $groupRepository->findOneBy(array(
                    'title' => $groupTitle,
                ));
                if ($group) {
                    $user->addGroup($group);
                } else {
                    $msg = sprintf('Self-registration group "%s" not found for user "%s"',
                        $groupTitle,
                        $user->getUsername());
                    /** @var LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->error($msg);
                }

            }

            $this->sendEmail($user);

            $em = $this->getEntityManager();
            $em->persist($user);
            $em->flush();

            $helperService->giveOwnRights($user);

            return $this->redirectToRoute('fom_user_registration_send');
        }

        return $this->render('@FOMUser/Registration/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Registration step 4: Activate account by token
     *
     * @Route("/user/activate", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function confirmAction(Request $request)
    {
        $user = $this->getUserFromRegistrationToken($request);
        if (!$user) {
            return $this->render('@FOMUser/Login/error-notoken.html.twig', array(
                'site_email' => $this->getEmailFromAdress(),
            ));
        }

        /** @var User $user */
        // Check token age
        $max_registration_age = $this->container->getParameter("fom_user.max_registration_time");
        if(!$this->checkTimeInterval($user->getRegistrationTime(), $max_registration_age)) {
            return $this->tokenExpired($user);
        }

        // Unset token
        $em = $this->getEntityManager();
        $user->setRegistrationToken(null);
        $em->flush();

        // Forward to final page
        return $this->redirectToRoute('fom_user_registration_done');
    }

    /**
     * Registration step 4a: Reset token (if expired)
     *
     * @Route("/user/registration/reset", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function resetAction(Request $request)
    {
        $user = $this->getUserFromRegistrationToken($request);
        if(!$user) {
            return $this->render('@FOMUser/Login/error-notoken.html.twig', array(
                'site_email' => $this->getEmailFromAdress(),
            ));
        }

        $user->setRegistrationToken(hash("sha1",rand()));
        $user->setRegistrationTime(new \DateTime());

        $this->sendEmail($user);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('fom_user_registration_send');
    }

    /**
     * Registration step 5: Welcome new user
     *
     * @Route("/user/registration/done", methods={"GET"})
     * @return Response
     */
    public function doneAction()
    {
        return $this->render('@FOMUser/Registration/done.html.twig');
    }

    /**
     * @param User $user
     */
    protected function sendEmail($user)
    {
       $fromName = $this->container->getParameter("fom_user.mail_from_name");
       $fromEmail = $this->getEmailFromAdress();
       $mailFrom = array($fromEmail => $fromName);
       /** @var \Swift_Mailer $mailer */
       $mailer = $this->get('mailer');
       $text = $this->renderView('FOMUserBundle:Registration:email-body.text.twig', array("user" => $user));
       $html = $this->renderView('FOMUserBundle:Registration:email-body.html.twig', array("user" => $user));
       $message = new \Swift_Message();
       $message
           ->setSubject($this->renderView('FOMUserBundle:Registration:email-subject.text.twig'))
           ->setFrom($mailFrom)
           ->setTo($user->getEmail())
           ->setBody($text)
           ->addPart($html, 'text/html')
       ;

       $mailer->send($message);
    }

    /**
     * @param Request $request
     * @return User|null
     */
    protected function getUserFromRegistrationToken(Request $request)
    {
        $token = $request->get('token');
        if ($token) {
            /** @var User|null $user */
            $user = $this->getUserRepository()->findOneBy(array(
                'registrationToken' => $token,
            ));
            return $user;
        } else {
            return null;
        }
    }
}
