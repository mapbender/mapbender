<?php

namespace Mapbender\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Welcome controller.
 *
 * This controller can be used to display an list of available applications.
 * It has been separated in it's own class so it can easily be added or
 * removed from the routing.
 *
 * @author Christian Wygoda
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class WelcomeController extends AbstractController
{
    public function __construct(protected ApplicationYAMLMapper $yamlRepository, protected ManagerRegistry $doctrine)
    {
    }

    /**
     * Render user application list.
     */
    #[Route(path: '/', methods: ['GET'])]
    public function list(): Response
    {
        $yamlApplications = $this->yamlRepository->getApplications();
        $dbApplications = $this->doctrine->getRepository(Application::class)->findBy([], [
            'title' => 'ASC',
        ]);
        /** @var Application[] $allApplications */
        $allApplications = array_merge($yamlApplications, $dbApplications);
        $allowedApplications = [];
        foreach ($allApplications as $application) {
            if ($this->isGranted(ResourceDomainApplication::ACTION_VIEW, $application)) {
                $allowedApplications[] = $application;
            }
        }
        return $this->render('@MapbenderCore/Welcome/list.html.twig', [
            'applications' => $allowedApplications,
            'create_permission' => $this->isGranted(ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS),
        ]);
    }
}
