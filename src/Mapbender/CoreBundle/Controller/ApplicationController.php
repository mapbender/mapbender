<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Application controller.
 *
 * @author Christian Wygoda
 */
class ApplicationController extends Controller {
    /**
     * Get runtime URLs
     *
     * @param string $slug
     * @return array
     */
    private function getUrls($slug) {
        return array(
            'base' => $this->get('request')->getBaseUrl(),
            // TODO: Can this be done less hack-ish?
            'asset' => rtrim($this->get('templating.helper.assets')
                ->getUrl('.'), '.'),
            'element' => $this->get('router')
                ->generate('mapbender_core_application_element', array(
                    'slug' => $slug)),
            'trans' => $this->get('router')
                ->generate('mapbender_core_translation_trans'),
            'proxy' => $this->get('router')
            ->generate('mapbender_core_proxy_proxy'));
    }


    /**
     * Asset controller.
     *
     * Dumps the assets for the given application and type. These are up to
     * date and this controller will be used during development mode.
     *
     * @Route("/application/{slug}/assets/{type}")
     */
    public function assetsAction($slug, $type) {
        $assets = $this->getApplication($slug)->getAssets($type);
        $asset_modification_time = new \DateTime();
        $asset_modification_time->setTimestamp($assets->getLastModified());

        //TODO: Make filters part of the bundle configuration
        //TODO: I'd like to have source maps support in here for easier
        //    debugging of minified code, see
        //    http://www.thecssninja.com/javascript/source-mapping
        $filters = array(
            'js' => array(),
            'css' => array($this->container->get('assetic.filter.cssrewrite')));

        // Set target path for CSS rewrite to work
        $target = $this->get('request')->server->get('DOCUMENT_ROOT')
		. $this->get('request')->server->get('REQUEST_URI');
        $assets->setTargetPath($target);

        foreach($filters[$type] as $filter) {
            $assets->ensureFilter($filter);
        }

        $mimetypes = array(
            'css' => 'text/css',
            'js' => 'application/javascript');

        $response = new Response();
        $response->headers->set('Content-Type', $mimetypes[$type]);

        if(!$this->container->getParameter('kernel.debug')) {
            //TODO: use max(asset_modification_time, application_update_time)
            $response->setLastModified($asset_modification_time);
            if($response->isNotModified($this->get('request'))) {
                return $response;
            }
        }

        $response->setContent($assets->dump());
        return $response;
    }

    /**
     * Element action controller.
     *
     * Passes the request to the element's httpAction.
     * @Route("/application/{slug}/element/{id}/{action}",
     *     defaults={ "id" = null, "action" = null })
     */
    public function elementAction($slug, $id, $action) {
        $element = $this->getApplication($slug)->getElement($id);

        $this->checkAllowedRoles($element->getRoles());

        return $element->httpAction($action);
    }

    /**
     * Main application controller.
     *
     * @Route("/application/{slug}.{_format}", defaults={ "_format" = "html" })
     * @Template()
     */
    public function applicationAction($slug) {
        $application = $this->getApplication($slug);

        // At this point, we are allowed to acces the application. In order
        // to use the proxy in following request, we have to mark the session
        $this->get("session")->set("proxyAllowed",true);

        return new Response($application->render());
    }

    /**
     * Get the application by slug.
     *
     * Tries to get the application with the given slug and throws an 404
     * exception if it can not be found. This also checks access control and
     * therefore may throw an AuthorizationException.
     *
     * @return Mapbender\CoreBundle\Component\Application
     */
    private function getApplication($slug) {
        $application = $this->get('mapbender')
            ->getApplication($slug, $this->getUrls($slug));

        if($application === null) {
            throw new NotFoundHttpException(
                'The application can not be found.');
        }

        $this->checkApplicationAccess($application);

        return $application;
    }

    /**
     * Check access permissions for given application.
     *
     * This will first check if the current user is the owner, in which case
     * access will be granted without regarding published state or required
     * roles for the application. The same is true for the root account user.
     * Then access will be denied if the application is not currently
     * published, and the lastly checkAccess will be called to check if the
     * current user has at least one of the roles required by the application.
     * Denied access or insufficient authorization will throw the corresponding
     * exception which are then picked up by Symfony's security layer.
     *
     * @param Application $application
     */
    public function checkApplicationAccess(Application $application) {
        $user = $this->get('security.context')->getToken()->getUser();
        $owner = $application->getEntity()->getOwner();

        if(is_object($user) && $owner->equals($user)) {
            return;
        }

        if($user instanceof User and $user->getId() == 1) {
            return;
        }

        if(!$application->getEntity()->isPublished()) {
            throw new AccessDeniedException('This application is not published at the moment.');
        }

        $this->checkAccess($application->getRoles());
    }

    /**
     * Check access permissions for given roles.
     *
     * Check if the allowed roles set by the application or element are matched
     * by the current user. If no roles are required by the application
     * configuration, this will still require IS_AUTHENTICATED_ANONYMOUSLY to
     * guarantee a session.
     * May throw an AccessDeniedException.
     */
    private function checkAccess(array $allowedRoles) {
        if(count($allowedRoles) == 0) {
            $allowedRoles = 'IS_AUTHENTICATED_ANONYMOUSLY';
        }

        if(!$this->get('security.context')->isGranted($allowedRoles)) {
            throw new AccessDeniedException(
                "You are not allowed to access this application");
        }
    }
}

