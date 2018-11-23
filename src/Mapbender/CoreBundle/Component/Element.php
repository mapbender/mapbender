<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\Component\BundleUtil;
use Mapbender\Component\ClassUtil;
use Mapbender\Component\StringUtil;
use Mapbender\CoreBundle\Entity\Element as Entity;
use Mapbender\ManagerBundle\Component\ElementFormFactory;
use Mapbender\ManagerBundle\Component\Mapper;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
abstract class Element
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

    /**  @var Entity Element configuration storage entity */
    protected $entity;

    /** @var array Class name parts */
    protected $classNameParts;

    /** @var array Element fefault configuration */
    protected static $defaultConfiguration = array();

    /** @var string translation subject */
    protected static $description  = "mb.core.element.class.description";

    /** @var string[] translation subject */
    protected static $tags = array();

    /** @var string[] translation subjects */
    protected static $title = "mb.core.element.class.title";

    /**
     * The constructor. Every element needs an application to live within and
     * the container to do useful things.
     *
     * @param Application        $application Application component
     * @param ContainerInterface $container   Container service
     * @param Entity             $entity
     */
    public function __construct(Application $application, ContainerInterface $container, Entity $entity)
    {
        $this->classNameParts = explode('\\', get_called_class());
        $this->application    = $application;
        $this->container      = $container;
        $this->entity         = $entity;
    }

    /*************************************************************************
     *                                                                       *
     *                              Class metadata                           *
     *                                                                       *
     *************************************************************************/

    /**
     * Returns the element class title
     *
     * This is primarily used in the manager backend when a list of available
     * elements is given.
     *
     * @return string
     */
    public static function getClassTitle()
    {
        return static::$title;
    }

    /**
     * Returns the element class description.
     *
     * This is primarily used in the manager backend when a list of available
     * elements is given.
     *
     * @return string
     */
    public static function getClassDescription()
    {
        return static::$description;
    }

    /**
     * Returns the element class tags.
     *
     * These tags are used in the manager backend to quickly filter the list
     * of available elements.
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
     *                    Configuration entity handling                      *
     *                                                                       *
     *************************************************************************/

    /**
     * Get a configuration value by path.
     *
     * Get the configuration value or null if the path is not defined. If you
     * ask for an path which has children, the configuration array with these
     * children will be returned.
     *
     * Configuration paths are lists of parameter keys seperated with a slash
     * like "targets/map".
     *
     * @param string $path The configuration path to retrieve.
     * @return mixed
     */
    final public function get($path)
    {
        throw new \RuntimeException('NIY get ' . $path . ' ' . get_class($this));
    }

    /**
     * Set a configuration value by path.
     *
     * @param string $path the configuration path to set
     * @param mixed $value the value to set
     */
    final public function set($path, $value)
    {
        throw new \RuntimeException('NIY set');
    }

    /**
     * Get the configuration entity.
     *
     * @return \Mapbender\CoreBundle\Entity\Element $entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /*************************************************************************
     *                                                                       *
     *             Shortcut functions for leaner Twig templates              *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the element ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Get the element title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->entity->getTitle();
    }

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
     * Should return the variables available for the frontend template. By default, this
     * is a single "configuration" value holding the entirety of the configuration from the element entity.
     * Override this if you want to unravel / strip / extract / otherwise prepare specific values for your
     * element template.
     *
     * NOTE: the default implementation of render automatically makes the following available:
     * * "element" (Element component instance)
     * * "application" (Application component instance)
     * * "entity" (Element entity instance)
     * * "id" (Element id, string)
     * * "title" (Element title from entity, string)
     *
     * You do not need to, and should not, produce them yourself again. If you do, your values will replace
     * the defaults!
     *
     * @return array
     */
    public function getFrontendTemplateVars()
    {
        return array(
            'configuration' => $this->getConfiguration(),
        );
    }

    /**
     * Return twig-style BundleName:section:file_name.engine.twig path to template file.
     *
     * Base implementation automatically calculates this from the class name. Override if
     * you cannot follow naming / placement conventions.
     *
     * @param string $suffix defaults to '.html.twig'
     * @return string
     */
    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return $this->getAutomaticTemplatePath($suffix);
    }

    /**
     * Should return the values available to the JavaScript client code. By default, this is the entirety
     * of the "configuration" array from the element entity, which is insecure if your element configuration
     * stores API keys, users / passwords embedded into URLs etc.
     *
     * If you have sensitive data anywhere near your element entity's configuration, you should override this
     * method and emit only the values you need for your JavaScript to work.
     *
     * @return array
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
     * Should return 2D array of asset references required by this Element to function.
     * Top-level keys are 'css', 'js', 'trans' (all optional). Within 'css' and 'js' subarrays, you can use
     * a) implicit bundle asset reference (based on concrete class namespace)
     *     'css/element.css'   => resolves to <web>/bundles/mapbendercore/css/element.css
     * b) explicit bundle asset reference
     *     '@MapbenderWmsBundle/Resources/public/css/element/something.js'
     * c) web-relative asset reference
     *     '/components/select2/select2-built.css
     *
     * The 'trans' sub-array should contain exclusively twig-style asset references (with ':' separators)
     *      to json.twig files. E.g.
     *     'MapbenderPrintBundle:Element:imageexport.json.twig'
     *
     * @return string[][] grouped asset references
     */
    public function getAssets()
    {
        return $this::listAssets();
    }

    /**
     * Get the configuration from the element entity.
     *
     * This should primarily be used for exporting / importing.
     *
     * You SHOULD NOT do transformations specifically for your twig template or your JavaScript here,
     * there are now dedicated methods exactly for that (see getFrontendTemplateVars and getPublicConfiguration).
     * Anything you do here in a method override needs to be verified against application export + reimport.
     *
     * The backend usually accesses the entity instance directly, so it won't be affected.
     *
     * @return array
     */
    public function getConfiguration()
    {

//        $configuration = $this->entity->getConfiguration();

        $configuration = $this->entity->getConfiguration();
//        $config = $this->entity->getConfiguration();
//        //@TODO merge recursive $this->entity->getConfiguration() and $this->getDefaultConfiguration()
//        $def_configuration = $this->getDefaultConfiguration();
//        $configuration = array();
//        foreach ($def_configuration as $key => $val) {
//            if(isset($config[$key])){
//                $configuration[$key] = $config[$key];
//            }
//        }
        return $configuration;
    }

    /**
     * Get the function name of the JavaScript widget for this element. This
     * will be called to initialize the element.
     *
     * @return string
     */
    public function getWidgetName()
    {
        return 'mapbender.mb' . end($this->classNameParts);
    }

    /**
     * Handle element Ajax requests.
     *
     * Do your magic here.
     *
     * @param string $action The action to perform
     * @return Response
     * @throws HttpException
     * @todo Symfony 3.x: update Element API to accept injected Request from controller
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
    public function trans($key, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->container->get('translator')->trans($key, $parameters);
    }

    /*************************************************************************
     *                                                                       *
     *                          Backend stuff                                *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the element configuration form type. By default, this class needs to have the same name
     * as the element, suffixed with "AdminType" and located in the Element\Type sub-namespace.
     *
     * Override this method and return null to get a simple YAML entry form.
     *
     * @return string Administration type class name
     */
    public static function getType()
    {
        return static::getAutomaticAdminType(null);
    }

    /**
     * Get the form template to use.
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
     * @param array $result the result configuration
     * @return array the result configuration
     */
    public static function mergeArrays($default, $main, $result)
    {
        foreach ($main as $key => $value) {
            if ($value === null) {
                $result[$key] = null;
            } elseif (is_array($value)) {
                if (isset($default[$key])) {
                    $result[$key] = Element::mergeArrays($default[$key], $main[$key], array());
                } else {
                    $result[$key] = $main[$key];
                }
            } else {
                $result[$key] = $value;
            }
        }
        if ($default !== null && is_array($default)) {
            foreach ($default as $key => $value) {
                if (!isset($result[$key]) || (isset($result[$key]) && $result[$key] === null && $value !== null)) {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Changes Element after save.
     */
    public function postSave()
    {

    }

    /**
     * Create form for given element
     *
     * @param ContainerInterface $container
     * @param \Mapbender\CoreBundle\Entity\Application $application
     * @param Entity             $element
     * @param bool               $onlyAcl
     * @return array
     * @deprecated use the service
     * @internal
     */
    public static function getElementForm($container, $application, Entity $element, $onlyAcl = false)
    {
        /** @var ElementFormFactory $formFactory */
        $formFactory = $container->get('mapbender.manager.element_form_factory.service');
        if ($onlyAcl) {
            return $formFactory->getSecurityForm($element);
        } else {
            return $formFactory->getConfigurationForm($application, $element);
        }
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
     * Converts a camel-case string to underscore-separated lower-case string
     *
     * E.g. "FantasticMethodNaming" => "fantastic_method_naming"
     *
     * @param $className
     * @return mixed
     * @internal
     * @deprecated to be removed in 3.0.8; use StringUtil::camelToSnakeCase directly
     */
    protected static function getTemplateName($className)
    {
        return StringUtil::camelToSnakeCase($className);
    }

    /**
     * Hook function for embedded elements to influence the effective application config on initial load.
     * We (can) use this for BaseSourceSwitchter (deactivates layers), SuggestMap element reloading state etc.
     *
     * @param array
     * @return array
     */
    public function updateAppConfig($configIn)
    {
        return $configIn;
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
            'slug' => $this->application->getEntity()->getSlug(),
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
}
