<?php
namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Application as AppComponent;
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
        $securityContext      = $this->get('security.context');
        $oid                  = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
        $applications         = $this->get('mapbender')->getApplicationEntities();
        $allowed_applications = array();
        foreach ($applications as $application) {
            if ($application->isExcludedFromList()) {
                continue;
            }

            if ($securityContext->isGranted('VIEW', $application)) {
                if (!$application->isPublished() && !$securityContext->isGranted('EDIT', $application)) {
                    continue;
                }
                $allowed_applications[] = $application;
            }
        }

        return array(
            'applications'      => $allowed_applications,
            'uploads_web_url'   => AppComponent::getUploadsUrl($this->container),
            'create_permission' => $securityContext->isGranted('CREATE', $oid),
            'time'              => new \DateTime()
        );
    }
}
