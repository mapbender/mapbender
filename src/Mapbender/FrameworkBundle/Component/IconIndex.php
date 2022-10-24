<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;

class IconIndex implements IconPackageInterface
{
    /** @var IconPackageInterface[] */
    protected $packages;

    /**
     * @param IconPackageInterface[] $packages
     */
    public function __construct($packages)
    {
        $this->packages = $packages;
    }

    public function getChoices()
    {
        $choices = array();
        foreach ($this->packages as $package) {
            // array_diff to remove value duplicates while preserving keys
            $choices += \array_diff($package->getChoices(), $choices);
        }
        return $choices;
    }

    public function getIconMarkup($iconCode)
    {
        foreach ($this->packages as $package) {
            if ($package->isHandled($iconCode)) {
                $markup = $package->getIconMarkup($iconCode);
                if (!$markup) {
                    throw new \LogicException("Icon package " . \get_class($package) . " produced no markup for {$iconCode}");
                }
                return $markup;
            }
        }
        // Fingers crossed
        return HtmlUtil::renderTag('span', '', array(
            'class' => 'mb-glyphicon ' . $iconCode,
        ));
    }

    public function getStyleSheets()
    {
        $styleSheets = array();
        foreach ($this->packages as $package) {
            $styleSheets = \array_merge($styleSheets, $package->getStyleSheets());
        }
        return \array_values(\array_unique($styleSheets));
    }

    public function isHandled($iconCode)
    {
        return true;
    }

    public function getAliases()
    {
        throw new \LogicException("Index package cannot list aliases");
    }

    public function normalizeAlias($iconCode)
    {
        foreach ($this->packages as $package) {
            $aliases = $package->getAliases();
            if (!empty($aliases[$iconCode])) {
                return $aliases[$iconCode];
            }
        }
        return $iconCode;
    }
}
