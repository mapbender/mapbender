<?php


namespace Mapbender\Component;


interface IconPackageInterface
{
    /**
     * Should return a list of supported icon codes, mapped by label.
     * This is used in the backend selection for assignable icons for
     * buttons etc.
     *
     * @return string[]
     */
    public function getChoices();

    /**
     * @param string $iconCode
     * @return string
     */
    public function getIconMarkup($iconCode);

    /**
     * Can this package render an icon for this code?
     * This can exceed the set advertised through getChoices, if
     * 1) icons are aliased (several icon codes produce the same icon)
     * or
     * 2) in fallback / catchall packages
     *
     * If not handled by this package, the system will try
     * the next (lower priority) package.
     *
     * @param string $iconCode
     * @return bool
     */
    public function isHandled($iconCode);

    /**
     * CSS file(s) for this package.
     * NOTE: these will be embedded directly as ~
     * <link rel="stylesheet" src="{{ asset(...) }}"/>
     * WITHOUT scss compilation. Paths for images and fonts should be relative
     * to the css file.
     *
     * @return string[]
     */
    public function getStyleSheets();

    /**
     * Should return a mapping of equivalent icon code => canonical icon code.
     *
     * @return string[]
     */
    public function getAliases();
}
