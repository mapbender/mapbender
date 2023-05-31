<?php
namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

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
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;

    public function __construct(ApplicationYAMLMapper $yamlRepository)
    {
        $this->yamlRepository = $yamlRepository;
    }

    /**
     * Render user application list.
     *
     * @Route("/", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function listAction(Request $request)
    {
        $yamlApplications = $this->yamlRepository->getApplications();
        $dbApplications = $this->getDoctrine()->getRepository(Application::class)->findBy(array(), array(
            'title' => 'ASC',
        ));
        /** @var Application[] $allApplications */
        $allApplications = array_merge($yamlApplications, $dbApplications);
        $allowedApplications = array();

        foreach ($allApplications as $application) {
            if ($this->isGranted('VIEW', $application)) {
                $allowedApplications[] = $application;
            }
        }

        return $this->render('@MapbenderCore/Welcome/list.html.twig', array(
            'applications'      => $allowedApplications,
            'create_permission' => $this
                ->isGranted('CREATE', new ObjectIdentity('class', Application::class)),
        ));
    }
}
