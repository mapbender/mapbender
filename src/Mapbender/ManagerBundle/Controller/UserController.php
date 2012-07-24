<?php

namespace Mapbender\ManagerBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Mapbender\CoreBundle\Entity\User;
use Mapbender\CoreBundle\Security\UserHelper;
use Mapbender\ManagerBundle\Form\Type\UserForgotPassType;
use Mapbender\ManagerBundle\Form\Type\UserResetPassType;
use Mapbender\ManagerBundle\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * User management controller
 *
 * @author Christian Wygoda
 */
class UserController extends Controller {
    /**
     * Renders user list.
     *
     * @Route("/user")
     * @Method({ "GET" })
     * @Template
     */
    public function indexAction() {
        $query = $this->getDoctrine()->getEntityManager()->createQuery(
            'SELECT r FROM MapbenderCoreBundle:User r');

        $users = $query->getResult();

        return array(
            'users' => $users);
    }

    /**
     * @Route("/user/new")
     * @Method({ "GET" })
     * @Template
     */
    public function newAction() {
        $user = new User();
        $form = $this->createForm(new UserType(), $user);

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user")
     * @Method({ "POST" })
     * @Template("MapbenderManagerBundle:User:new.html.twig")
     */
    public function createAction() {
        $user = new User();
        $form = $this->createForm(new UserType(), $user);

        $form->bindRequest($this->get('request'));

        if($form->isValid()) {
            // Set encrypted password and create new salt
            // The unencrypted password is already set on the user!
            $helper = new UserHelper($this->container);
            $helper->setPassword($user, $user->getPassword());
            
            $user->setRegistrationTime(new \DateTime());
            
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($user);
            $em->flush();

            $this->get('session')->setFlash('success',
                'The user has been saved.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_user_index'));
        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user/{id}/edit")
     * @Method({ "GET" })
     * @Template
     */
    public function editAction($id) {
        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
            ->find($id);
        if($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        $form = $this->createForm(new UserType(), $user, array(
            'requirePassword' => false));

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user/{id}/update")
     * @Method({ "POST" })
     * @Template("MapbenderManagerBundle:User:edit.html.twig")
     */
    public function updateAction($id) {
        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
            ->find($id);
        if($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        // If no password is given, we'll recycle the old one
        $request = $this->get('request');
        $userData = $request->get('user');
        $keepPassword = false;
        if($userData['password']['first'] === ''
            && $userData['password']['second'] === '') {
            $userData['password'] = array(
                'first' => $user->getPassword(),
                'second' => $user->getPassword());

            $keepPassword = true;
        }

        $form = $this->createForm(new UserType(), $user, array(
            'requirePassword' => false));
        $form->bind($userData);

        if($form->isValid()) {
            if(!$keepPassword) {
                // Set encrypted password and create new salt
                // The unencrypted password is already set on the user!
                $helper = new UserHelper($this->container);
                $helper->setPassword($user, $user->getPassword());
            }

            $em = $this->getDoctrine()->getEntityManager();
            $em->flush();

            $this->get('session')->setFlash('success',
                'The user has been updated.');

            return $this->redirect(
                $this->generateUrl('mapbender_manager_user_index'));

        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user/{id}/delete")
     * @Method({ "GET" })
     * @Template("MapbenderManagerBundle:User:delete.html.twig")
     */
    public function confirmDeleteAction($id) {
        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
            ->find($id);
        if($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        $form = $this->createDeleteForm($id);

        return array(
            'user' => $user,
            'form' => $form->createView());
    }

    /**
     * @Route("/user/{id}/delete")
     * @Method({ "POST" })
     * @Template
     */
    public function deleteAction($id) {
        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
            ->find($id);
        if($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);
        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($user);
            $em->flush();

            $this->get('session')->setFlash('success',
                'The user has been deleted.');
        } else {
            $this->get('session')->setFlash('error',
                'The user couldn\'t be deleted.');
        }
        return $this->redirect(
            $this->generateUrl('mapbender_manager_user_index'));
    }
    
    

    /**
     * @Route("/user/forgotpass")
     * @Method({"GET"})
     * @Template("MapbenderManagerBundle:User:form-forgotpass.html.twig")
     */
    public function forgotpassformAction() {
        $this->checkSelfRegister();
        $user = new User();
        $form = $this->createForm(new UserForgotPassType(), $user);
        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }
    
    /**
     * @Route("/user/forgotpass")
     * @Method({"POST"})
     * @Template("MapbenderManagerBundle:User:form-forgotpass.html.twig")
     */
    public function forgotpassAction() {
        $this->checkSelfRegister();
        $userReq = new User();
        $form = $this->createForm(new UserForgotPassType(), $userReq);
        $form->bindRequest($this->get('request'));

        $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
                ->findOneByUsername($userReq->getUsername());
        if($user == null) {
            $user = $this->getDoctrine()->getRepository('MapbenderCoreBundle:User')
                ->findOneByEmail($userReq->getUsername());
        }
        
        if($user == null) {
            $form = $this->createForm(new UserForgotPassType(), $userReq);
            $form->addError(new FormError($this->get("templating")->render(
                    'MapbenderManagerBundle:User:forgotpass-user-notfound.html.twig',
                    array("user" => $userReq))));
            return  array(
                'user' => $userReq,
                'form' => $form->createView(),
                'form_name' => $form->getName());
        } else if($user->getRegistrationToken() != null) {
            $form = $this->createForm(new UserForgotPassType(), $user);
            $form->addError(new FormError($this->get("templating")->render(
                    'MapbenderManagerBundle:User:forgotpass-user-notactivated.html.twig',
                    array("user" => $user))));
            return  array(
                'user' => $user,
                'form' => $form->createView(),
                'form_name' => $form->getName());
        } else {
            $form = $this->createForm(new UserType(), $user);
            $user->setResetToken(hash("sha1",rand()));
            $user->setResetTime(new \DateTime());
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($user);
            $em->flush();
            //send email
            $mailFrom = array($this->container->getParameter("mail_from_address") => $this->container->getParameter("mail_from_name"));
            $mailer = $this->get('mailer');
            
            $text = $this->get("templating")->render(
                    'MapbenderManagerBundle:User:forgotpass-text.email.twig',
                    array("user" => $user));
            $html = $this->get("templating")->render(
                    'MapbenderManagerBundle:User:forgotpass-html.email.twig',
                    array("user" => $user));
            $message = \Swift_Message::newInstance()
                    ->setSubject($this->get("templating")->render(
                            'MapbenderManagerBundle:User:forgotpass-subject.text.twig',
                            array()))
                    ->setFrom($mailFrom)
                    ->setTo($user->getEmail())
                    ->setBody($this->get("templating")->render(
                            'MapbenderManagerBundle:User:forgotpass-text.email.twig',
                            array("user" => $user)))
                    ->addPart($this->get("templating")->render(
                            'MapbenderManagerBundle:User:forgotpass-html.email.twig',
                            array("user" => $user)), 'text/html');
            $mailer->send($message);
            return $this->render(
                    'MapbenderManagerBundle:User:forgotpass-confirm.html.twig',
                    array("user" => $user));
        }
    }
    
    /**
     * @Route("/user/resetpass/{token}")
     * @Method({"GET"})
     * @Template("MapbenderManagerBundle:User:form-resetpass.html.twig")
     */
    public function resetPassFormAction($token){
        $this->checkSelfRegister();
        $user = $this->getDoctrine()->getRepository("MapbenderCoreBundle:User")->findOneByResetToken($token);
        if($user !== null){
            if(!$this->checkTimeInterval($user->getResetTime(),
                    $this->container->getParameter("mapbender.max_reset_time"))){
                $form = $this->createForm(new UserForgotPassType(), $user);
                $form->addError(new FormError($this->get("templating")->render(
                        'MapbenderManagerBundle:User:resetpass-token-expired.text.twig',
                        array())));
                return $this->render(
                    'MapbenderManagerBundle:User:form-forgotpass.html.twig',
                    array(
                        'user' => $user,
                        'form' => $form->createView(),
                        'form_name' => $form->getName()));
            }
            $form = $this->createForm(new UserResetPassType(), $user);
            return  array(
                'user' => $user,
                'form' => $form->createView(),
                'form_name' => $form->getName());
        } else {
            return $this->render(
                    'MapbenderManagerBundle:User:resetpass-pass-changed.html.twig',
                    array());
        }
    }
    
    /**
     * @Route("/user/resetpass/{token}")
     * @Method({"POST"})
     * @Template("MapbenderManagerBundle:User:form-resetpass.html.twig")
     */
    public function resetPassAction($token){
        $this->checkSelfRegister();
        $user = $this->getDoctrine()->getRepository("MapbenderCoreBundle:User")->findOneByResetToken($token);
        if($user){
            if(!$this->checkTimeInterval($user->getResetTime(),
                    $this->container->getParameter("mapbender.max_reset_time"))){
                $form = $this->createForm(new UserForgotPassType(), $user);
                $form->addError(new FormError($this->get("templating")->render(
                        'MapbenderManagerBundle:User:resetpass-token-expired.html.twig',
                        array())));
                return $this->render(
                    'MapbenderManagerBundle:User:form-forgotpass.html.twig',
                    array(
                        'user' => $user,
                        'form' => $form->createView(),
                        'form_name' => $form->getName()));
            }
            $form = $this->createForm(new UserResetPassType(), $user);
            $form->bindRequest($this->get('request'));
            if($form->isValid()) {
                $em = $this->getDoctrine()->getEntityManager();
                $user->setResetToken(null);
                $helper = new UserHelper($this->container);
                $helper->setPassword($user, $user->getPassword());
                $em->persist($user);
                $em->flush();
                return $this->render(
                        'MapbenderManagerBundle:User:resetpass-confirm.html.twig',
                        array());
            } else {
                $form->addError(new FormError($this->get("templating")->render(
                    'MapbenderManagerBundle:User:resetpass-pass-wrong.html.twig',
                    array())));
                return array(
                    'user' => $user,
                    'form' => $form->createView(),
                    'form_name' => $form->getName());
                
            }
        } else {
            return $this->render(
                    'MapbenderManagerBundle:User:resetpass-pass-changed.html.twig',
                    array());
        }
    }
    
    /**
     * @Route("/user/registerconfirm/{token}")
     * @Method({"GET"})
     * @Template("MapbenderManagerBundle:User:register-user-accept.html.twig")
     */
    public function registerConfirmAction($token){
        $this->checkSelfRegister();
        $user = $this->getDoctrine()->getRepository("MapbenderCoreBundle:User")
                ->findOneByRegistrationToken($token);
        if($user){
            if(!$this->checkTimeInterval($user->getRegistrationTime(),
                    $this->container->getParameter("mapbender.max_registration_time"))){
                $form = $this->createForm(new UserForgotPassType(), $user);
                $form->addError(new FormError($this->get("templating")->render(
                        'MapbenderManagerBundle:User:register-token-expired.text.twig',
                        array())));
                return $this->render(
                    'MapbenderManagerBundle:User:form-register-token-expired.html.twig',
                    array(
                        'user' => $user,
                        'form' => $form->createView(),
                        'form_name' => $form->getName()));
            }
            
            
            
            $em = $this->getDoctrine()->getEntityManager();
            $user->setRegistrationToken(null);
            $em->persist($user);
            $em->flush();
            return array(
                        "user" => $user);
        } else {
            return $this->render(
                    'MapbenderManagerBundle:User:register-user-error.html.twig',
                    array("user" => $user));
        }
    }
    
    
    /**
     * @Route("/user/registerreset/{token}")
     * @Method({"POST"})
     * @Template("MapbenderManagerBundle:User:register.html.twig")
     */
    public function registerResetAction($token) {
        $this->checkSelfRegister();
        $user = $this->getDoctrine()->getRepository("MapbenderCoreBundle:User")
                ->findOneByRegistrationToken($token);
        if($user){
            $em = $this->getDoctrine()->getEntityManager();
            $user->setRegistrationToken(hash("sha1",rand()));
            $em->persist($user);
            $em->flush();
            $mailFrom =array($this->container->getParameter("mail_from_address") => $this->container->getParameter("mail_from_name"));
            $mailer = $this->get('mailer');
            
            $text = $this->get("templating")->render(
                    'MapbenderManagerBundle:User:register-text.email.twig',
                    array("user" => $user));
            $html = $this->get("templating")->render(
                    'MapbenderManagerBundle:User:register-html.email.twig',
                    array("user" => $user));
            $message = \Swift_Message::newInstance()
                    ->setSubject($this->get("templating")->render(
                            'MapbenderManagerBundle:User:register-subject.text.twig',
                            array()))
                    ->setFrom($mailFrom)
                    ->setTo($user->getEmail())
                    ->setBody($this->get("templating")->render(
                            'MapbenderManagerBundle:User:register-text.email.twig',
                            array("user" => $user)))
                    ->addPart($this->get("templating")->render(
                            'MapbenderManagerBundle:User:register-html.email.twig',
                            array("user" => $user)), 'text/html');
            $mailer->send($message);
            return $this->render(
                    'MapbenderManagerBundle:User:register-user-confirm.html.twig',
                    array("user" => $user));
        } else {
            return $this->render(
                    'MapbenderManagerBundle:User:register-user-error.html.twig',
                    array("user" => $user));
        }
    }
    
    /**
     * @Route("/user/register")
     * @Method({"GET"})
     * @Template("MapbenderManagerBundle:User:register.html.twig")
     */
    public function registerformAction() {
        $this->checkSelfRegister();
        $user = new User();
        $form = $this->createForm(new UserType(), $user);

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/user/register")
     * @Method({"POST"})
     * @Template("MapbenderManagerBundle:User:register.html.twig")
     */
    public function registerAction() {
        $this->checkSelfRegister();
        $user = new User();
        $form = $this->createForm(new UserType(), $user);
        $form->bindRequest($this->get('request'));
        if($form->isValid()) {
            $helper = new UserHelper($this->container);
            $helper->setPassword($user, $user->getPassword());
            $user->setRegistrationToken(hash("sha1",rand()));
            $user->setRegistrationTime(new \DateTime());
//            $user->setRoles(array('ROLE_USER'));
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($user);
            $em->flush();
            $mailFrom =array($this->container->getParameter("mail_from_address") => $this->container->getParameter("mail_from_name"));
            $mailer = $this->get('mailer');
            
            $text = $this->get("templating")->render(
                    'MapbenderManagerBundle:User:register-text.email.twig',
                    array("user" => $user));
            $html = $this->get("templating")->render(
                    'MapbenderManagerBundle:User:register-html.email.twig',
                    array("user" => $user));
            $message = \Swift_Message::newInstance()
                    ->setSubject($this->get("templating")->render(
                            'MapbenderManagerBundle:User:register-subject.text.twig',
                            array()))
                    ->setFrom($mailFrom)
                    ->setTo($user->getEmail())
                    ->setBody($this->get("templating")->render(
                            'MapbenderManagerBundle:User:register-text.email.twig',
                            array("user" => $user)))
                    ->addPart($this->get("templating")->render(
                            'MapbenderManagerBundle:User:register-html.email.twig',
                            array("user" => $user)), 'text/html');
            $mailer->send($message);
            return $this->render(
                    'MapbenderManagerBundle:User:register-user-confirm.html.twig',
                    array("user" => $user));
        }
        
        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }
    
    protected function checkSelfRegister(){
        if(!$this->container->getParameter('mapbender.selfregister'))
            throw new AccessDeniedHttpException();
    }
    
    protected function checkTimeInterval($startTime, $timeInterval){
        $checktime = new \DateTime();
        $checktime->sub(new \DateInterval(sprintf("PT%dH",$timeInterval)));
        if($startTime < $checktime) {
            return false;
        } else{
            return true;
        }
    }

    /**
     * Creates the form for the confirm delete page.
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }
}

