<?php
namespace Mapbender\CoreBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Mapbender\CoreBundle\Entity\Group;
use Mapbender\CoreBundle\Form\GroupType;

/**
 * Description of GroupController
 *
 * @author apour
 */
class GroupController extends Controller {

    /**
     * @Route("/group/")
     * @Method("GET")
     * @Template()
     * @ParamConverter("groupList",class="Mapbender\CoreBundle\Entity\Group")
     */
    public function indexAction(array $groupList) {
        return array(
            "groupList" => $groupList
        );
    }

    /**
     * @Route("/group/create")
     * @Method("GET")
     * @Template()
     */
    public function createAction() {
        $form = $this->get("form.factory")->create(
                new GroupType(),
                new Group()
        );


        return array(
            "form" => $form->createView()
        );
    }

    /**
     * @Route("/group/")
     * @Method("POST")
     */
    public function addAction() {
        $group = new Group();

        $form = $this->get("form.factory")->create(
                new GroupType(),
                $group
        );

        $request = $this->get("request");

        $form->bindRequest($request);

        if($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($group);
            $em->flush();
            return $this->redirect($this->generateUrl("mapbender_core_group_index"));
        } else {
            return $this->render(
                "MapbenderCoreBundle:Group:create.html.twig",
                array("form" => $form->createView())
            );
        }
    }


    /**
     * @Route("/group/{groupId}")
     * @Method("GET")
     * @Template()
     */
    public function editAction(Group $group) {
        $form = $this->get("form.factory")->create(
                new GroupType(),
                $group
        );

        return array(
            "form" => $form->createView(),
            "group" => $group
        );
    }

    /**
     * @Route("/group/{groupId}/delete")
     * @Method("POST")
     */
    public function deleteAction(Group $group) {
        $em = $this->getDoctrine()->getEntityManager();
        try {
            $em->remove($group);
            $em->flush();
        } catch(\Exception $E) {
            $this->get("logger")->info("Could not delete group. ".$E->getMessage());
            $this->get("session")->setFlash("error","Could not delete group.");
            return $this->redirect($this->generateUrl("mapbender_core_group_index"));
        }

        $this->get("session")->setFlash("info","Succsessfully deleted.");
        return $this->redirect($this->generateUrl("mapbender_core_group_index"));
    }

    /**
     * @Route("/group/{groupId}/delete")
     * @Method("GET")
     * @Template()
     */
    public function confirmdeleteAction(Group $group) {

        return array(
            "group" => $group
        );
    }

    /**
     * @Route("/group/{groupId}")
     * @Method("POST")
     */
    public function saveAction(Group $group) {
        $form = $this->get("form.factory")->create(
                new GroupType(),
                $group
        );

        $request = $this->get("request");

        $form->bindRequest($request);

        if($form->isValid()) {
            try {
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($group);
                $em->flush();
            } catch(\Exception $E) {
                $this->get("logger")->error("Could not save group. ".$E->getMessage());
                $this->get("session")->setFlash("error","Could not save group");
                return $this->redirect($this->generateUrl("mapbender_core_group_edit",array("groupId" => $group->getId())));
            }
            return $this->redirect($this->generateUrl("mapbender_core_group_index"));
        } else {
            return $this->render(
                "MapbenderCoreBundle:Group:edit.html.twig",
                array(
          "form" => $form->createView(),
                    "group" => $group
        )
            );
        }
    }
}
