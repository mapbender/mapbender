<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Element as Entity;
use Mapbender\ManagerBundle\Component\Mapper;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**  @var Entity The configuration storage entity */
    protected $entity;

    /** @var array Class name parts */
    protected $classNameParts;

    /** @var array Element fefault configuration */
    protected static $defaultConfiguration = array();

    /** @var string Element description translation subject */
    protected static $description  = "mb.core.element.class.description";

    /** @var string Element title translation subject */
    protected static $tags = array();

    /** @var string[] Element tag translation subjects */
    protected static $title = "mb.core.element.class.title";

    /**
     * The constructor. Every element needs an application to live within and
     * the container to do useful things.
     *
     * @param Application $application The application object
     * @param ContainerInterface $container The container object
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
     * @return object $entity
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
        $parts    = $this->classNameParts;
        $template = $parts[0] . $parts[1] . ":" . $parts[2] . ":" . static::getTemplateName($parts[3]) . ".html.twig";

        return $this->container->get('templating')->render($template,
            array(
                'element'       => $this,
                'id'            => $this->getId(),
                'entity'        => $this->entity,
                'title'         => $this->getTitle(),
                'configuration' => $this->getConfiguration()
            ));
    }

    /**
     * Get the element assets.
     *
     * Returns an array of references to asset files of the given type.
     * Assets are grouped by css and javascript.
     * References can either be filenames/path which are searched for in the
     * Resources/public directory of the element's bundle or assetic references
     * indicating the bundle to search in:
     *
     * array(
     *   'foo.css'),
     *   '@MapbenderCoreBundle/Resources/public/foo.css'));
     *
     * @return array
     */
    public static function listAssets()
    {
        return array();
    }

    /**
     * Get the element assets.
     *
     * This should be a subset of the static function listAssets. Assets can be
     * removed from the overall list depending on the configuration for
     * example. By default, the same list as by listAssets is returned.
     *
     * @return array
     */
    public function getAssets()
    {
        return $this::listAssets();
    }

    /**
     * Get the publicly exposed configuration, usually directly derived from
     * the configuration field of the configuration entity. If you, for
     * example, store passwords in your element configuration, you should
     * override this method to return a cleaned up version of your
     * configuration which can safely be exposed in the client.
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
     */
    public function httpAction($action)
    {
        throw new NotFoundHttpException('This element has no Ajax handler.');
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
     * Get the element configuration form type.
     *
     * Override this method to provide a custom configuration form instead of
     * the default YAML form.
     *
     * @return string Administration type class name
     */
    public static function getType()
    {
        $clsInfo = explode('\\', get_called_class());
        return $clsInfo[0] . '\\' . $clsInfo[1] . '\\' . $clsInfo[2] . '\\Type\\' . $clsInfo[3] . 'AdminType';
    }

    /**
     * Get the form template to use.
     *
     * @return string
     */
    public static function getFormTemplate()
    {
        $clsInfo = explode('\\', get_called_class());
        return $clsInfo[0] .  $clsInfo[1] . ':' . $clsInfo[2] . 'Admin:' . static::getTemplateName($clsInfo[3]) . '.html.twig';
    }

    /**
     * Get the form assets.
     *
     * @return array
     */
    public static function getFormAssets()
    {
        return array(
            'js' => array(),
            'css' => array());
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
     * @param        $container
     * @param        $application
     * @param Entity $element
     * @param bool   $onlyAcl
     * @return array
     * @internal param string $class
     */
    public static function getElementForm($container, $application, Entity $element, $onlyAcl = false)
    {
        /** @var Element $class */
        $class = $element->getClass();

        // Create base form shared by all elements
        $formType = $container->get('form.factory')->createBuilder('form', $element, array());
        if (!$onlyAcl) {
            $formType->add('title', 'text')
                ->add('class', 'hidden')
                ->add('region', 'hidden');
        }
        $formType->add(
            'acl',
            'acl',
            array(
                'mapped' => false,
                'data' => $element,
                'create_standard_permissions' => false,
                'permissions' => array(
                    1 => 'View'
                )
            )
        );

        // Get configuration form, either basic YAML one or special form
        $configurationFormType = $class::getType();
        if ($configurationFormType === null) {
            $formType->add(
                'configuration',
                new YAMLConfigurationType(),
                array(
                    'required' => false,
                    'attr' => array(
                        'class' => 'code-yaml'
                    )
                )
            );
            $formTheme = 'MapbenderManagerBundle:Element:yaml-form.html.twig';
            $formAssets = array(
                'js' => array(
                    'components/codemirror/lib/codemirror.js',
                    'components/codemirror/mode/yaml/yaml.js',
                    'bundles/mapbendermanager/js/form-yaml.js'),
                'css' => array(
                    'components/codemirror/lib/codemirror.css'));
        } else {
            $type = new $configurationFormType();
            $options = array('application' => $application);
            if ($type instanceof ExtendedCollection && $element !== null && $element->getId() !== null) {
                $options['element'] = $element;
            }
            $formType->add('configuration', $type, $options);
            $formTheme = $class::getFormTemplate();
            $formAssets = $class::getFormAssets();
        }

        return array(
            'form' => $formType->getForm(),
            'theme' => $formTheme,
            'assets' => $formAssets);
    }

    /**
     * Create default element
     *
     * @param Element $class
     * @param string $region
     * @return \Mapbender\CoreBundle\Entity\Element
     */
    public static function getDefaultElement($class, $region)
    {
        $element = new Entity();
        $configuration = $class::getDefaultConfiguration();
        $element
            ->setClass($class)
            ->setRegion($region)
            ->setWeight(0)
            ->setTitle($class::getClassTitle())
            ->setConfiguration($configuration);

        return $element;
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
     * Get template name by class name
     *
     * @param $className
     * @return mixed
     */
    protected static function getTemplateName($className)
    {
        $className = preg_replace_callback(
            '/[A-Z]+[^A-Z]+/',
            function ($match) {
                return "_".strtolower($match[0]);
            },
            $className
        );
        $className = substr($className,1);
        return $className;
    }
}
