<?php
namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Application as AppComponent;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\CoreBundle\Entity\Application;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
     * Render user application list.
     *
     * @Route("/")
     * @Template()
     */
    public function listAction()
    {
        $securityContext     = $this->getContext();
        $applications        = $this->get('mapbender')->getApplicationEntities();
        $allowedApplications = array();

        foreach ($applications as $application) {
            if ($application->isExcludedFromList()) {
                continue;
            }

            if ($securityContext->isUserAllowedToView($application)) {
                if (!$application->isPublished()
                    && !$securityContext->isUserAllowedToEdit($application)) {
                    continue;
                }
                $allowedApplications[] = $application;
            }
        }

        return array(
            'applications'      => $allowedApplications,
            'uploads_web_url'   => AppComponent::getUploadsUrl($this->container),
            'create_permission' => $securityContext->isUserAllowedToCreate(new Application()),
            'time'              => new \DateTime()
        );
    }

    /**
     * @return SecurityContext
     */
    protected function getContext()
    {
        return $this->get('security.context');
    }

    /**
     * Translate string;
     *
     * @param string $key Key name
     * @return string
     */
    protected function translate($key)
    {
        return $this->get('translator')->trans($key);
    }
}
