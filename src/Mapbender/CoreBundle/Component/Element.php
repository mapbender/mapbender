<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\Component\BundleUtil;
use Mapbender\Component\ClassUtil;
use Mapbender\Component\StringUtil;
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

    /**
     * Merge Configurations. The merge_configurations defines, if the default
     * configuration array and the configuration array should be merged
     * @var boolean merge configurations
     */
    public static $merge_configurations = true;

    /** @var Application Application component */
    protected $application;

    /** @var ContainerInterface Symfony container */
    protected $container;

    /** @var array Element fefault configuration */
    protected static $defaultConfiguration = array();

    /** @var string translation subject */
    protected static $description  = "mb.core.element.class.description";

    /** @var string[] translation subject */
    protected static $tags = array();

    /** @var string[] translation subjects */
    protected static $title = "mb.core.element.class.title";

    /**
     * Do not override or even copy this constructor into your child class.
     * This method will be made final in a future release.
     *
     * @param Application        $application Application component
     * @param ContainerInterface $container   Container service
     * @param Entity             $entity
     */
    public function __construct(Application $application, ContainerInterface $container, Entity $entity)
    {
        $this->application    = $application;
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
     * Returns the element class tags, which have absolutely no effect on anything.
     *
     * @return array
     */
    public static function getClassTags()
    {
        return static::$tags;
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
     *             Shortcut functions for leaner Twig templates              *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the element description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->entity->getDescription();
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
            'element'       => $this,
            'id'            => $this->getId(),
            'entity'        => $this->entity,
            'title'         => $this->getTitle(),
            'application'   => $this->application,
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
    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return $this->getAutomaticTemplatePath($suffix);
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
        return static::getAutomaticAdminType(null);
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
        return static::getAutomaticTemplatePath('.html.twig', 'ElementAdmin', null);
    }

    /**
     *  Merges the default configuration array and the configuration array
     *
     * @param array $default the default configuration of an element
     * @param array $main the configuration of an element
     * @return array the result configuration
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
     * Get lowercase element name from full class namespace
     *
     * @param string $class
     * @return string
     */
    protected static function getElementName($class)
    {
        $namespaceParts = explode('\\', $class);

        return strtolower(array_pop($namespaceParts));
    }

    /**
     * Changes a element entity configuration while exporting.
     *
     * @param array $formConfiguration element entity configuration
     * @param array $entityConfiguration element entity configuration
     * @return array a configuration
     */
    public function normalizeConfiguration(array $formConfiguration, array $entityConfiguration = array())
    {
        return $formConfiguration;
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

    ##### Automagic calculation for template paths and admin type ####

    /**
     * Generates an automatic template path for the element from its class name.
     *
     * The generated path is twig engine style, i.e.
     * 'BundleClassName:section:template_name.engine-name.twig'
     *
     * The bundle name is auto-generated from the first two namespace components, e.g. "MabenderCoreBundle".
     *
     * The resource section (=subfolder in Resources/views) defaults to "Element" but can be supplied.
     *
     * The file name is the in-namespace class name lower-cased and & decamelized, plus the given $suffix,
     * which defaults to '.html.twig'. E.g. "html_element.html.twig".
     *
     * E.g. for a Mapbender\FoodBundle\Element\LemonBomb element child class this will return
     * "MapbenderFoodBundle:Element:lemon_bomb.html.twig".
     * This would correspond to this file path:
     * <mapbender-root>/src/Mapbender/FoodBundle/Resources/views/Element/lemon_bomb.html.twig
     *
     * @param string $suffix to be appended to the generated path (default: '.html.twig', try '.json.twig' etc)
     * @param string|null $resourceSection if null, will use third namespace component (i.e. first non-bundle component).
     *                    For elements in any conventional Mapbender bundle, this will be "Element".
     *                    We also use "ElementAdmin" in certain places though.
     * @param bool|null $inherit allow inheriting template names from parent class, excluding the (abstract)
     *                  Element class itself; null (default) for auto-decide (blacklist controlled)
     * @return string twig-style Bundle:Section:file_name.ext
     * @deprecated this entire machinery is only relevant to mapbender/data-source::BaseElement and will
     *    be moved there; each Element should declare its admin template explicitly to facilitate usage searches
     */
    public static function getAutomaticTemplatePath($suffix = '.html.twig', $resourceSection = null, $inherit = null)
    {
        if ($inherit === null) {
            return static::getAutomaticTemplatePath($suffix, $resourceSection, static::autoDetectInheritanceRule());
        }

        if ($inherit) {
            $cls = ClassUtil::getBaseClass(get_called_class(), __CLASS__, false);
        } else {
            $cls = get_called_class();
        }
        $bundleName = BundleUtil::extractBundleNameFromClassName($cls);
        $postBundleNamespaceParts = explode('\\', BundleUtil::getNameInsideBundleNamespace($cls));
        $nameWithoutNamespace = implode('', array_slice($postBundleNamespaceParts, -1));

        $resourceSection = $resourceSection ?: "Element";
        $resourcePathParts = array_slice($postBundleNamespaceParts, 1, -1);   // subfolder under section, often empty
        $resourcePathParts[] = StringUtil::camelToSnakeCase($nameWithoutNamespace);

        return "{$bundleName}:{$resourceSection}:" . implode('/', $resourcePathParts) . $suffix;
    }

    /**
     * Generates an automatic "AdminType" class name from the element class name.
     *
     * E.g. for a Mapbender\FoodBundle\Element\LemonBomb element child class this will return the string
     * "Mapbender\FoodBundle\Element\Type\LemonBombAdminType"
     *
     * @param bool|null $inherit allow inheriting admin type from parent class, excluding the (abstract)
     *                  Element class itself; null (default) for auto-decide (blacklist controlled)
     * @return string
     * @deprecated this entire machinery is only relevant to mapbender/data-source::BaseElement and will
     *    be moved there; each Element should declare its admin type explicitly to facilitate usage searches
     */
    public static function getAutomaticAdminType($inherit = null)
    {
        if ($inherit === null) {
            return static::getAutomaticAdminType(static::autoDetectInheritanceRule());
        }
        if ($inherit) {
            $cls = ClassUtil::getBaseClass(get_called_class(), __CLASS__, false);
        } else {
            $cls = get_called_class();
        }
        $clsInfo = explode('\\', $cls);
        $namespaceParts = array_slice($clsInfo, 0, -1);
        // convention: AdminType classes are placed into the "<bundle>\Element\Type" namespace
        $namespaceParts[] = "Type";
        $bareClassName = implode('', array_slice($clsInfo, -1));
        // convention: AdminType class name is the same as the element class name suffixed with AdminType
        return implode('\\', $namespaceParts) . '\\' . $bareClassName . 'AdminType';
    }

    /**
     * Walk up through the class hierarchy and return the name of the first-generation child class immediately
     * inheriting from the abstract Element.
     *
     * @return string fully qualified class name
     * @deprecated use ClassUtil::getBaseClass directly
     */
    protected static function getNonAbstractBaseClassName()
    {
        return ClassUtil::getBaseClass(get_called_class(), __CLASS__, false);
    }

    /**
     * Determines if admin type / template / frontend template will be inherited if one of the getAutomatic*
     * methods is called with inherit = null.
     *
     * We do this because the intuitive default behavior (inherit everything from parent) is incompatible with
     * a certain, small set of elements. These elements are blacklisted for inheritance here. If the calling
     * class is one of them, or a child, we will not inherit admin type / templates (this method will return false).
     * Otherwise we do.
     *
     * @return bool
     * @deprecated this entire machinery is only relevant to mapbender/data-source::BaseElement and will
     *    be moved there
     */
    protected static function autoDetectInheritanceRule()
    {
        $inheritanceBlacklist = array(
            'Mapbender\DataSourceBundle\Element\BaseElement',
            'Mapbender\DigitizerBundle\Element',
        );
        $cls = get_called_class();
        foreach ($inheritanceBlacklist as $noInheritClass) {
            if (is_a($cls, $noInheritClass, true)) {
                return false;
            }
        }
        return true;
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
}
