<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Asset\ApplicationAssetCache;
use Mapbender\CoreBundle\Asset\AssetFactory;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Application controller.
 *
 * @author Christian Wygoda
 */
class ApplicationController extends Controller
{

    /**
     * Get runtime URLs
     * Hack to get proper urls when embedded in drupal
     *
     * @param string $slug
     * @return array
     */
    private function getUrls($slug)
    {
        $config        = array('slug' => $slug);
        $router        = $this->get('router');
        $searchSubject = 'mapbender';
        $drupal_mark   = function_exists('mapbender_menu') ? '?q=mapbender' : $searchSubject;

        $urls = array(
            'base'     => $this->get('request')->getBaseUrl(),
            'asset'    => $this->get('templating.helper.assets')->getUrl(null),
            'element'  => $router->generate('mapbender_core_application_element', $config),
            'trans'    => $router->generate('mapbender_core_translation_trans'),
            'proxy'    => $router->generate('owsproxy3_core_owsproxy_entrypoint'),
            'metadata' => $router->generate('mapbender_core_application_metadata', $config));

        if ($searchSubject !== $drupal_mark) {
            foreach ($urls as $k => $v) {
                if ($k == "asset") {
                    continue;
                }
                $urls[ $k ] = str_replace($searchSubject, $drupal_mark, $v);
            }
        }

        return $urls;
    }

    /**
     * Asset controller.
     *
     * Dumps the assets for the given application and type. These are up to
     * date and this controller will be used during development mode.
     *
     * @Route("/application/{slug}/assets/{type}")
     * @param string $slug Application slug name
     * @param string $type Asset type
     * @return Response
     */
    public function assetsAction($slug, $type)
    {
        $response         = new Response();
        $request          = $this->getRequest();
        $env              = $this->container->get("kernel")->getEnvironment();
        $isProduction     = $env == "prod";
        $cacheFile        = $this->getCachedAssetPath($slug, $env, $type);
        $needCache        = $isProduction && !file_exists($cacheFile);
        $modificationDate = new \DateTime();
        $application      = $this->getApplication($slug);

        $response->headers->set('Content-Type', $this->getMimeType($type));

        if ($isProduction && !$needCache) {
            $modificationTs = filectime($cacheFile);
            $isAppDbBased   = $application->getEntity()->getSource() === ApplicationEntity::SOURCE_DB;
            $modificationDate->setTimestamp($modificationTs);

            if (!$isAppDbBased || ($isAppDbBased && $application->getEntity()->getUpdated() < $modificationDate)) {
                $response->setLastModified($modificationDate);
                $response->headers->set('X-Asset-Modification-Time', $modificationDate->format('c'));
                if ($response->isNotModified($request)) {
                    return $response;
                }
                $response->setContent(file_get_contents($cacheFile));
                return $response;
            }
        }

        if ($type == "css") {
            $sourcePath = $request->getBasePath();
            $refs       = array_unique($application->getAssets('css'));
            $custom     = $application->getCustomCssAsset();
            if ($custom) {
                $refs[] = $custom;
            }
            $factory = new AssetFactory($this->container, $refs, 'css', $request->server->get('REQUEST_URI'), empty($sourcePath) ? "." : $sourcePath);
            $content = $factory->compile();
        } else {
            $cache   = new ApplicationAssetCache($this->container, $application->getAssets($type), $type);
            $assets  = $cache->fill($slug, 0);
            $content = $assets->dump();
        }

        if ($isProduction && $needCache) {
            file_put_contents($cacheFile, $content);
        }

        return $response->setContent($content);
    }

    /**
     * Element action controller.
     *
     * Passes the request to
     * the element's httpAction.
     * @Route("/application/{slug}/element/{id}/{action}",
     *     defaults={ "id" = null, "action" = null },
     *     requirements={ "action" = ".+" })
     */
    public function elementAction($slug, $id, $action)
    {
        $element = $this->getApplication($slug)->getElement($id);

        return $element->httpAction($action);
    }

    /**
     * Main application controller.
     *
     * @Route("/application/{slug}.{_format}", defaults={ "_format" = "html" })
     */
    public function applicationAction($slug)
    {
        $env          = $this->container->get("kernel")->getEnvironment();
        $isProduction = $env == "prod";
        $response     = new Response();
        $session      = $this->get("session");
        $application  = $this->getApplication($slug);

        if ($isProduction) {
            $cacheFile = $this->getCachedAssetPath($slug . "-" . session_id(), $env, "html");

            if (!is_file($cacheFile)) {
                $content = $application->render();
                file_put_contents($cacheFile, $content);
                $session->set("proxyAllowed", true);
                $response->setContent($content);
            } else {
                $modificationDate = new \DateTime();
                $modificationDate->setTimestamp(filectime($cacheFile));
                $isAppDbBased = $application->getEntity()->getSource() === ApplicationEntity::SOURCE_DB;
                if (!$isAppDbBased || ($isAppDbBased && $application->getEntity()->getUpdated() < $modificationDate)) {
                    $response->setLastModified($modificationDate);
                    $response->headers->set('X-Asset-Modification-Time', $modificationDate->format('c'));
                    if ($response->isNotModified($this->getRequest())) {
                        return $response;
                    }
                }
                $response->setContent(file_get_contents($cacheFile));
            }
        } else {
            $session->set("proxyAllowed", true);
            $response->setContent($application->render());
        }

        return $response;
    }

    /**
     * Get the application by slug.
     *
     * Tries to get the application with the given slug and throws an 404
     * exception if it can not be found. This also checks access control and
     * therefore may throw an AuthorizationException.
     *
     * @return Application
     */
    private function getApplication($slug)
    {
        $application = $this->get('mapbender')->getApplication($slug, $this->getUrls($slug));

        if (!$application) {
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
    public function checkApplicationAccess(Application $application)
    {
        $securityContext = $this->get('security.context');

        $application_entity = $application->getEntity();
        if ($application_entity::SOURCE_YAML === $application_entity->getSource() && count($application_entity->yaml_roles)) {

            // If no token, then check manually if some role IS_AUTHENTICATED_ANONYMOUSLY
            if (!$securityContext->getToken()) {
                if (in_array('IS_AUTHENTICATED_ANONYMOUSLY', $application_entity->yaml_roles)) {
                    return;
                }
            }

            $passed = false;
            foreach ($application_entity->yaml_roles as $role) {
                if ($securityContext->isGranted($role)) {
                    $passed = true;
                    break;
                }
            }
            if (!$passed) {
                throw new AccessDeniedException('You are not granted view permissions for this application.');
            }
        }

        $granted = $securityContext->isGranted('VIEW', $application_entity);
        if (false === $granted) {
            throw new AccessDeniedException('You are not granted view permissions for this application.');
        }

        if (!$application_entity->isPublished() and ! $securityContext->isGranted('EDIT', $application_entity)) {
            throw new AccessDeniedException('This application is not published at the moment');
        }
    }

    /**
     * Metadata controller.
     *
     * @Route("/application/{slug}/metadata")
     */
    public function metadataAction($slug)
    {
        $securityContext = $this->get('security.context');
        $sourceId = $this->container->get('request')->get("sourceId", null);
        $instance = $this->container->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($sourceId);
        if (!$securityContext->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application'))
            && !$securityContext->isGranted('VIEW', $instance->getLayerset()->getApplication())) {
            throw new AccessDeniedException();
        }
// TODO source access ?
//        if (!$securityContext->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source'))
//            && !$securityContext->isGranted('VIEW', $instance->getSource())) {
//            throw new AccessDeniedException();
//        }

        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$instance->getManagertype()];

        $path = array('_controller' => $manager['bundle'] . ":" . "Repository:metadata");
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle(
                $subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Get SourceInstances via HTTP Basic Authentication
     *
     * @Route("/application/{slug}/instance/{instanceId}/tunnel")
     */
    public function instanceTunnelAction($slug, $instanceId)
    {
        $instance = $this->container->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\SourceInstance')->find($instanceId);
        $securityContext = $this->get('security.context');

        if (!$securityContext->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application'))
            && !$securityContext->isGranted('VIEW', $instance->getLayerset()->getApplication())) {
            throw new AccessDeniedException();
        }
// TODO source access ?
//        if (!$securityContext->isGranted('VIEW', new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source'))
//            && !$securityContext->isGranted('VIEW', $instance->getSource())) {
//            throw new AccessDeniedException();
//        }
//        $params = $this->getRequest()->getMethod() == 'POST' ?
//            $this->get("request")->request->all() : $this->get("request")->query->all();
        $headers = array();
        $postParams = $this->get("request")->request->all();
        $getParams = $this->get("request")->query->all();
        $user = $instance->getSource()->getUsername() ? $instance->getSource()->getUsername() : null;
        $password = $instance->getSource()->getUsername() ? $instance->getSource()->getPassword() : null;
        $instHandler = EntityHandler::createHandler($this->container, $instance);
        $vendorspec = $instHandler->getSensitiveVendorSpecific();
        /* overwrite vendorspecific parameters from handler with get/post parameters */
        if (count($getParams)) {
            $getParams = array_merge($vendorspec, $getParams);
        }
        if (count($postParams)) {
            $postParams = array_merge($vendorspec, $postParams);
        }
        $proxy_config = $this->container->getParameter("owsproxy.proxy");
        $proxy_query = ProxyQuery::createFromUrl(
                $instance->getSource()->getGetMap()->getHttpGet(), $user, $password, $headers, $getParams, $postParams);
        $proxy = new CommonProxy($proxy_config, $proxy_query);
        $browserResponse = $proxy->handle();
        $response = new Response();
        $response->setContent($browserResponse->getContent());
        return $response;
    }

    /**
     * @param $slug
     * @param $env
     * @param $type
     * @return string
     */
    public function getCachedAssetPath($slug, $env, $type)
    {
        return $this->container->getParameter('kernel.root_dir') . "/cache/" . $env . "/" . $slug . ".min." . $type;
    }

    /**
     * Get mime type
     *
     * @param string $type
     * @return array
     */
    protected function getMimeType($type)
    {
        static $types = array(
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'trans' => 'application/javascript');
        return $types[$type];
    }
}
