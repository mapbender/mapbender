<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\ElementBase\BoundSelfRenderingInterface;
use Mapbender\CoreBundle\Component\ElementBase\MinimalBound;
use Mapbender\CoreBundle\Entity\Element as Entity;
use Mapbender\ManagerBundle\Component\Mapper;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Base class for all Mapbender elements.
 *
 * This class defines all base methods and required instance methods to
 * implement an Mapbender3 element.
 *
 * @author Christian Wygoda
 *
 * @deprecated switch to service type-elements ASAP for Symfony 4+ compatibility
 * @see \Mapbender\Component\Element\AbstractElementService
 * @todo 3.3: remove this class
 */
abstract class Element extends MinimalBound
    implements ElementInterface, ElementHttpHandlerInterface, BoundSelfRenderingInterface
{
    /**
     * Extended API. The ext_api defines, if an element can be used as a target
     * element.
     * @var boolean extended api
     */
    public static $ext_api = true;

    /** @var ContainerInterface Symfony container */
    protected $container;

    /** @var array Element fefault configuration */
    protected static $defaultConfiguration = array();

    /** @var string translation subject */
    protected static $description  = "mb.core.element.class.description";

    /** @var string[] translation subjects */
    protected static $title = "mb.core.element.class.title";

    /**
     * Do not override or even copy this constructor into your child class.
     * This method will be made final in a future release.
     *
     * @param ContainerInterface $container
     * @param Entity             $entity
     */
    public function __construct(ContainerInterface $container, Entity $entity)
    {
        $this->container      = $container;
        parent::__construct($entity);
    }

    /*************************************************************************
     *                                                                       *
     *                              Class metadata                           *
     *                                                                       *
     *************************************************************************/

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return static::$title;
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return static::$description;
    }

    /**
     * Returns the default element options.
     *
     * You should specify all allowed options here with their default value.
     *
     * @return array
     */
    public static function getDefaultConfiguration()
    {
        return static::$defaultConfiguration;
    }

    /*************************************************************************
     *                                                                       *
     *                              Frontend stuff                           *
     *                                                                       *
     *************************************************************************/

    /**
     * Render the element HTML fragment.
     *
     * @return string
     */
    public function render()
    {
        $defaultTemplateVars = array(
            'id'            => $this->entity->getId(),
            'title'         => $this->entity->getTitle(),
        );
        $templateVars = array_replace($defaultTemplateVars, $this->getFrontendTemplateVars());
        $templatePath = $this->getFrontendTemplatePath();

        /** @var TwigEngine $templatingEngine */
        $templatingEngine = $this->container->get('templating');
        return $templatingEngine->render($templatePath, $templateVars);
    }

    /**
     * @inheritdoc
     */
    public function getFrontendTemplateVars()
    {
        return array(
            'configuration' => $this->getConfiguration(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getPublicConfiguration()
    {
        return $this->getConfiguration();
    }

    /**
     * @return string[][]
     * @deprecated less useful form of getAssets, which you should implement instead
     */
    public static function listAssets()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        // @todo 3.1: remove listAssets inflection. This will break:
        //        data-manager < 1.0.6.2
        //        query-builder < 1.0.2
        //        digitizer < 1.1.67
        //        A LOT of project Elements :\
        return $this::listAssets();
    }

    /**
     * Legacy fallback for both getPublicConfiguration and getFrontendTemplateVars. Defaults to returning the bound
     * Entity's 'configuration' attribute.
     * @return array
     */
    public function getConfiguration()
    {
        return $this->entity->getConfiguration();
    }

    /**
     * Should respond to the incoming http request.
     * Default implmentation delegates to the old-style httpAction method, which will cause problems in Symfony 3.
     *
     * Modern Element implementations should override this method, and not httpAction, so they can access the
     * controller's injected request object instead of attempting to get it from the container.
     *
     * NOTE: If you require access to the matched 'action' portion of the url route, you can get it like this:
     *       $action = $request->attributes->get('action');
     *       The controller route definition guarantees that this value will be present and non-empty.
     *
     * @param Request $request
     * @return Response
     * @since v3.0.8-beta1
     */
    public function handleHttpRequest(Request $request)
    {
        @trigger_error("Deprecated: " . get_class($this) . " only implements the old httpAction that does not support request injection, and will be incompatible with Symfony >=3", E_USER_DEPRECATED);
        return $this->httpAction($request->attributes->get('action'));
    }

    /**
     * Handle element Ajax requests.
     * Should only be implemented for compatibility with older Mapbender releases.
     * For current developments, implementation of handleHttpRequest is preferable because it will avoid issues
     * with newer Symfony versions.
     *
     * @param string $action The action to perform
     * @return Response
     * @throws HttpException
     */
    public function httpAction($action)
    {
        throw new NotFoundHttpException('This element has no Ajax handler.');
    }

    /**
     * Gets the user making the current request. Useful for httpAction.
     * Return type is a bit flexible, @see AbstractToken::setUser
     *
     * @return UserInterface|string|object|null
     */
    protected function getUser()
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->container->get('security.token_storage');
        $token = $tokenStorage->getToken();
        if ($token) {
            return $token->getUser();
        } else {
            return null;
        }
    }

    /**
     * Translate subject by key
     *
     * @param       $key
     * @param array $parameters
     * @param null  $domain
     * @param null  $locale
     * @return string
     */
    protected function trans($key, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->container->get('translator')->trans($key, $parameters);
    }

    /*************************************************************************
     *                                                                       *
     *                          Backend stuff                                *
     *                                                                       *
     *************************************************************************/

    /**
     * Should return the element configuration form type for backend configuration. Acceptable values are
     * * fully qualified service id (string)
     * * fully qualified PHP class name (string)
     * * Any object implementing Symfony FormTypeInterface (this also includes AbstractType children)
     * * null for a fallback Yaml textarea
     *
     * Default implementation will concatenate "AdminType" to the Element Component class name and look for that
     * class in the Element\Type subnamespace of the originating bundle.
     * Automatic class name calculation is deprecated. The default implementation will be removed in a future release,
     * making this method abstract. Return your form types explicitly.
     *
     * @return string
     */
    public static function getType()
    {
        return null;
    }

    /**
     * Should return a twig-style 'BundleName:section:filename.html.twig' reference to the HTML template used
     * for rendering the backend configuration form.
     *
     * Default implementation will convert the class name using camel to snake case, append '.html.twig' and look
     * for that file in the originating bundle's Resources/views/ElementAdmin section.
     * Automatic template inference is deprecated. The default implementation will be removed in a future
     * release, making this method abstract. Return your form templates explicitly.
     *
     * @return string
     */
    public static function getFormTemplate()
    {
        return null;
    }

    /**
     * Rewrite of array_replace_recursive with implicit null filter
     * @deprecated remove on master branch; use the appropriate array functions directly
     *
     * @param array $default replacement target
     * @param array $main replacements
     * @return array
     */
    public static function mergeArrays($default, $main)
    {
        $result = array();
        foreach ($main as $key => $value) {
            if (is_array($value) && isset($default[$key])) {
                $result[$key] = Element::mergeArrays($default[$key], $main[$key]);
            } else {
                $result[$key] = $value;
            }
        }
        if (is_array($default)) {
            foreach ($default as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Changes a element entity configuration while importing.
     *
     * @param array $configuration element entity configuration
     * @param Mapper $mapper a mapper object
     * @return array a configuration
     */
    public function denormalizeConfiguration(array $configuration, Mapper $mapper)
    {
        return $configuration;
    }

    /**
     * @param string $action
     * @param mixed $referenceType optional; one of the UrlGenerator constants
     * @return string
     */
    public function getHttpActionUrl($action, $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        /** @var UrlGeneratorInterface $router */
        $router = $this->container->get('router');
        $params = array(
            'slug' => $this->getEntity()->getApplication()->getSlug(),
            'id' => $this->entity->getId(),
            'action' => $action,
        );
        return $router->generate('mapbender_core_application_element', $params, $referenceType);
    }

    /**
     * Return map engine code ('ol4' or 'mq-ol2'). Convenience getter.
     *
     * @return string
     */
    public function getMapEngineCode()
    {
        return $this->entity->getApplication()->getMapEngineCode();
    }

    /**
     * Returns configured map scales, sorted ascending
     *
     * @param Entity|null $element
     * @return int[]
     */
    protected function getMapScales(Entity $element = null)
    {
        $element = $element ?: $this->entity;
        // @todo: map is central to application, should be accessible more directly (must enforce exactly 1 map per application first)
        $target = $element->getTargetElement('target');
        $scales = array();
        if ($target) {
            $mapConfig = $target->getConfiguration();
            if (!empty($mapConfig['scales'])) {
                $scales = array_map('intval', $mapConfig['scales']);
                asort($scales, SORT_NUMERIC | SORT_REGULAR);
            }
        }
        return $scales;
    }
}
