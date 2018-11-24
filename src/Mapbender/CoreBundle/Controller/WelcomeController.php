<?php
namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Application as AppComponent;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Mapbender;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
     * Render user application list.
     *
     * @Route("/")
     * @Template()
     */
    public function listAction()
    {
        $applications        = $this->getMapbender()->getApplicationEntities();
        $allowedApplications = array();

        foreach ($applications as $application) {
            if ($application->isExcludedFromList()) {
                continue;
            }
            if ($this->isGranted('VIEW', $application)) {
                if ($this->isGranted('EDIT', $application) || $application->isPublished()) {
                    $allowedApplications[] = $application;
                }
            }
        }
        $oid = new ObjectIdentity('class', get_class(new Application()));
        $createPermission = $this->isGranted('CREATE', $oid);
        return array(
            'applications'      => $allowedApplications,
            'uploads_web_url'   => AppComponent::getUploadsUrl($this->container),
            'create_permission' => $createPermission,
            'time'              => new \DateTime()
        );
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
