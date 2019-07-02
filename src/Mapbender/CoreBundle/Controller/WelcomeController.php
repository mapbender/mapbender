<?php
namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Controller\ApplicationControllerBase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
class WelcomeController extends ApplicationControllerBase
{
    /**
     * Render user application list.
     *
     * @Route("/", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function listAction(Request $request)
    {
        $allowedApplications = array();

        foreach ($this->getMapbender()->getApplicationEntities() as $application) {
            try {
                $this->checkApplicationAccess($application);
                $allowedApplications[] = $application;
            } catch (AccessDeniedException $e) {
                // skip silently
            }
        }

        return $this->render('@MapbenderCore/Welcome/list.html.twig', array(
            'applications'      => $allowedApplications,
            'uploads_web_url' => $this->getUploadsBaseUrl($request),
            'time'              => new \DateTime(),
            'create_permission' => $this
                ->isGranted('CREATE', new ObjectIdentity('class', get_class(new Application()))),
        ));
    }
}
