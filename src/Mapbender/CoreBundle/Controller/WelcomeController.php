<?php
namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Mapbender;
use Mapbender\ManagerBundle\Controller\ApplicationControllerBase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
            if ($this->isGranted('VIEW', $application)
                && ($this->isGranted('EDIT', $application) || $application->isPublished())
                && !$application->isExcludedFromList()) {
                $allowedApplications[] = $application;
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

    /**
     * Get Mapbender core service
     * @return Mapbender
     */
    protected function getMapbender()
    {
        /** @var Mapbender $service */
        $service = $this->get('mapbender');
        return $service;
    }
}
