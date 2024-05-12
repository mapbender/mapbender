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
    public function __construct(protected PermissionManager $permissionManager)
    {
    }

    public const CATEGORY_APPLICATION = "applications";
    public const CATEGORY_SOURCES = "sources";
    public const CATEGORY_PERMISSIONS = "permissions";
    public const CATEGORY_USERS = "users";
    public const CATEGORY_GROUPS = "groups";

    public static function categoryList(): array
    {
        return [
            self::CATEGORY_APPLICATION => "mb.terms.application.plural",
            self::CATEGORY_SOURCES => "mb.terms.source.plural",
            self::CATEGORY_PERMISSIONS => "fom.user.userbundle.classes.permissions",
            self::CATEGORY_USERS => "fom.user.userbundle.classes.users",
            self::CATEGORY_GROUPS => "fom.user.userbundle.classes.groups",
        ];
    }

    /**
     * @Route("/security/edit/{category}", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request, string $category)
    {
        $this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_MANAGE_PERMISSION);

        $actions = match ($category) {
            self::CATEGORY_APPLICATION => [
                ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS,
                ResourceDomainInstallation::ACTION_VIEW_ALL_APPLICATIONS,
                ResourceDomainInstallation::ACTION_EDIT_ALL_APPLICATIONS,
                ResourceDomainInstallation::ACTION_DELETE_ALL_APPLICATIONS,
                ResourceDomainInstallation::ACTION_OWN_ALL_APPLICATIONS
            ],
            self::CATEGORY_SOURCES => [
                ResourceDomainInstallation::ACTION_VIEW_SOURCES,
                ResourceDomainInstallation::ACTION_CREATE_SOURCES,
                ResourceDomainInstallation::ACTION_REFRESH_SOURCES,
                ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES,
                ResourceDomainInstallation::ACTION_DELETE_SOURCES,
            ],
            self::CATEGORY_PERMISSIONS => [
                ResourceDomainInstallation::ACTION_MANAGE_PERMISSION
            ],
            self::CATEGORY_USERS => [
                ResourceDomainInstallation::ACTION_VIEW_USERS,
                ResourceDomainInstallation::ACTION_CREATE_USERS,
                ResourceDomainInstallation::ACTION_EDIT_USERS,
                ResourceDomainInstallation::ACTION_DELETE_USERS,
            ],
            self::CATEGORY_GROUPS => [
                ResourceDomainInstallation::ACTION_VIEW_GROUPS,
                ResourceDomainInstallation::ACTION_CREATE_GROUPS,
                ResourceDomainInstallation::ACTION_EDIT_GROUPS,
                ResourceDomainInstallation::ACTION_DELETE_GROUPS,
            ],
            default => throw $this->createNotFoundException("Invalid category $category")
        };

        $form = $this->createForm(FormType::class, null, array(
            'label' => false,
        ));

        $resourceDomain = $this->permissionManager->findResourceDomainFor(null, throwIfNotFound: true);
        $form->add('security', PermissionListType::class, [
            'resource_domain' => $resourceDomain,
            'action_filter' => $actions,
            'entry_options' => [
                'action_filter' => $actions,
                'resource_domain' => $resourceDomain,
            ],
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
            'permission_class' => self::categoryList()[$category],
        ));
    }

    /**
     * @Route("/permission/overview", methods={"GET"})
     */
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
