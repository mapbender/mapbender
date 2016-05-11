<?php
namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Application as AppComponent;
use Mapbender\CoreBundle\Component\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
class WelcomeController extends Controller
{
    /**
     * List applications.
     *
     * @Route("/")
     * @Template()
     */
    public function listAction()
    {
        /** @var SecurityContext $securityContext */
        $securityContext     = $this->get('security.context');
        $oid                 = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
        $applications        = $this->get('mapbender')->getApplicationEntities();
        $allowedApplications = array();
        foreach ($applications as $application) {
            if ($application->isExcludedFromList()) {
                continue;
            }

            if ($securityContext->isUserAllowedToView($application)) {
                if (!$application->isPublished() && !$securityContext->isUserAllowedToEdit($application)) {
                    continue;
                }
                $allowedApplications[] = $application;
            }
        }

        return array(
            'applications'      => $allowedApplications,
            'uploads_web_url'   => AppComponent::getUploadsUrl($this->container),
            'create_permission' => $securityContext->isGranted('CREATE', $oid),
            'time'              => new \DateTime()
        );
    }
}
