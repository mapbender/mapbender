<?php


namespace Mapbender\CoreBundle\Component;


/**
 * Interface for Mapbender Element Component classes.
 * When writing / updating Elements, only implement methods from this interface, and optionally
 * @see ElementHttpHandlerInterface.
 *
 * @todo: separate further by frontend / backend concerns
 */
interface ElementInterface extends IAssetDependent
{
    /**
     * Should return string title of Element for
     * 1) backend: selecting a new element when adding to an application
     * 2) backend: default value for "title" field in Element Entity
     *
     * Return value is subject to translation
     * @return string
     */
    public static function getClassTitle();

    /**
     * Should return the long-form element description, which is displayed just under the title in the list of available
     * Elements, when adding to an application in the backend.
     *
     * Subject to translation.
     *
     * @return string
     */
    public static function getClassDescription();

    /**
     * Should return the (namespaced) JavaScript widget constructor name. E.g. 'mapbender.mbAboutDialog'.
     *
     * @return string
     */
    public function getWidgetName();

    /**
     * Should return a twig-style BundleName:section:file_name.engine.twig reference to the frontend HTML template.
     *
     * NOTE: The $suffix argument is no longer relevant. You may safely ignore it and return a hard-coded
     * 'something.html.twig'.
     *
     * @param string $suffix defaults to '.html.twig'
     * @return string
     */
    public function getFrontendTemplatePath($suffix = '.html.twig');

    /**
     * Should return the variables available in the frontend twig template. By default, this
     * is a single "configuration" value holding the entirety of the configuration array from the element entity.
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
    public function getFrontendTemplateVars();

    /**
     * Should return the values available to the JavaScript client code. Your JavaScript widget will be initialized
     * with these (they will land in the options attribute).
     * By default, this is the entirety of the "configuration" array from the element entity, which is insecure if
     * your element configuration stores API keys, users / passwords embedded into URLs etc.
     *
     * This data is visible over the /application/slug/config route and easily inspectable in a browser.
     * If you have sensitive data anywhere near your element entity's configuration, you should override this
     * method and emit only the values you need for your JavaScript to work.
     *
     * @return array
     */
    public function getPublicConfiguration();

    /**
     * Should return 2D array of asset references required by this Element to function in the frontend.
     * Top-level keys are 'css', 'js', 'trans' (all optional). Within 'css' and 'js' subarrays, you can use
     * a) implicit bundle asset reference (based on concrete class namespace)
     *     'css/element.css'   => resolves to <web>/bundles/mapbendercore/css/element.css
     * b) explicit bundle asset reference (preferred)
     *     '@MapbenderWmsBundle/Resources/public/element/something.js'
     * c) web-relative asset reference
     *     '/components/select2/select2-built.css
     *
     * The 'trans' sub-array should contain exclusively twig-style asset references (with ':' separators)
     *      to json.twig files. E.g.
     *     'MapbenderPrintBundle:Element:imageexport.json.twig'
     *
     * @return string[][] grouped asset references
     */
    public function getAssets();

    /**
     * Should return the element configuration form type for backend configuration. Acceptable values are
     * * fully qualified service id (string)
     * * fully qualified PHP class name (string)
     * * Any object implementing Symfony FormTypeInterface (this also includes AbstractType children)
     * * null for a fallback Yaml textarea
     *
     * @return string|null
     */
    public static function getType();

    /**
     * Should return a twig-style 'BundleName:section:filename.html.twig' reference to the HTML template used
     * for rendering the backend configuration form.
     *
     * @return string
     */
    public static function getFormTemplate();
}
