<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
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
            // @TODO: Can this be done less hack-ish?
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
        $response = new Response();
        $application = $this->getApplication($slug);
        $assets = $application->getAssets($type);
        $asset_modification_time = new \DateTime();
        $asset_modification_time->setTimestamp($assets->getLastModified());

        // @TODO: Make filters part of the bundle configuration
        // @TODO: I'd like to have source maps support in here for easier
        //      debugging of minified code, see
        //      http://www.thecssninja.com/javascript/source-mapping
        $filters = array(
            'js' => array(),
            'css' => array($this->container->get('assetic.filter.cssrewrite')));

        // Set target path for CSS rewrite to work
        // Replace backward slashes (Windows paths) with forward slashes...
        $target = str_replace('\\', '/', $this->get('request')->server->get('SCRIPT_FILENAME')
            . $this->get('request')->server->get('PATH_INFO'));

        $mimetypes = array(
            'css' => 'text/css',
            'js' => 'application/javascript');

        $application_update_time = new \DateTime();
        $application_entity = $this->getApplication($slug)->getEntity();

        // Determine last-modified timestamp for both DB- and YAML-based apps
        if($application->getEntity()->getSource() === ApplicationEntity::SOURCE_DB) {
            $updateTime = max($application->getEntity()->getUpdated(),
                $asset_modification_time);
        } else {
            $cacheUpdateTime = new \DateTime($this->container->getParameter('mapbender.cache_creation'));
            $updateTime = max($cacheUpdateTime, $asset_modification_time);
        }

        $response->setLastModified($updateTime);
        if($response->isNotModified($this->get('request'))) {
            return $response;
        }

        // @TODO: I'd rather use $assets->dump, but that clones each asset
        // which assigns a new weird targetPath. Gotta check that some time.
        $parts = array();
        foreach($assets->all() as $asset) {
            foreach($filters[$type] as $filter) {
                $asset->ensureFilter($filter);
            }
            $asset->setTargetPath($target);
            $parts[] = $asset->dump();
        }


        $response->headers->set('Content-Type', $mimetypes[$type]);
        $response->setContent(implode("\n", $parts));
        return $response;
    }

    /**
     * Element action controller.
     *
     * Passes the request to the element's httpAction.
     * @Route("/application/{slug}/element/{id}/{action}",
     *     defaults={ "id" = null, "action" = null },
     *     requirements={ "action" = ".+" })
     */
    public function elementAction($slug, $id, $action) {
        $element = $this->getApplication($slug)->getElement($id);

        //$this->checkAllowedRoles($element->getRoles());

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
     * This will check if any ACE in the ACL for the given applications entity
     * grants the VIEW permission.
     *
     * @param Application $application
     */
    public function checkApplicationAccess(Application $application) {
        $securityContext = $this->get('security.context');
        $granted = $securityContext->isGranted('VIEW', $application->getEntity());
        if(false === $granted) {
            throw new AccessDeniedException('You are not granted view permissions for this application.');
        }

        if(!$application->getEntity()->isPublished() and !$securityContext->isGranted('EDIT', $application->getEntity())) {
            throw new AccessDeniedException('This application is not published at the moment');
        }
    }
}

