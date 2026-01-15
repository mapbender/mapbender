<?php

namespace FOM\UserBundle\Controller;

use FOM\ManagerBundle\Configuration\Route;
use FOM\UserBundle\Form\Type\PermissionListType;
use FOM\UserBundle\Security\Permission\AssignableSubject;
use FOM\UserBundle\Security\Permission\PermissionManager;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends AbstractController
{
    public function __construct(
        protected PermissionManager          $permissionManager,
        protected ResourceDomainInstallation $installationPermissions,
    )
    {
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/security/edit/{category}', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $category)
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_MANAGE_PERMISSION);

        $actions = $this->installationPermissions->getPermissions($category);
        if (empty($actions)) {
            throw $this->createNotFoundException("Invalid category $category");
        }

        $form = $this->permissionManager->createPermissionForm(null, [
            'entry_options' => ['action_filter' => $actions],
            'show_public_access' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->permissionManager->savePermissions(null, $form->get('security')->getData(), $actions);

            return $this->redirectToRoute('fom_user_security_index', array(
                '_fragment' => 'tabPermissions',
            ));
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Your form has errors, please review them below.');
        }

        return $this->render('@FOMUser/Permission/edit.html.twig', array(
            'class' => $category,
            'form' => $form->createView(),
            'permission_class' => $this->installationPermissions->getCategoryList()[$category],
        ));
    }

    #[Route('/permission/overview', methods: ['GET'])]
    public function overview(Request $request): Response
    {
        $assignableSubjects = $this->permissionManager->getAssignableSubjects();

        $existingSubjectsJson = $this->readExistingSubjectsJson($request);
        if ($existingSubjectsJson) {
            $assignableSubjects = array_filter(
                $assignableSubjects,
                fn(AssignableSubject $subject) => !in_array($subject->getSubjectJson(), $existingSubjectsJson)
            );
        }

        return $this->render('@FOMUser/Permission/groups-and-users.html.twig', array(
            'subjects' => $assignableSubjects
        ));
    }

    private function readExistingSubjectsJson(Request $request): ?array
    {
        $existingSubjects = $request->query->get('subjects');
        if ($existingSubjects) {
            return json_decode($existingSubjects, true);
        }
        return null;
    }
}
