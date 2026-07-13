<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;

class IconIndex implements IconPackageInterface
{
    /**
     * @param IconPackageInterface[] $packages
     */
    public function __construct(protected $packages)
    {
    }

    /**
     * @return mixed[]
     */
    public function getChoices(): array
    {
        $choices = [];
        foreach ($this->packages as $package) {
            // array_diff to remove value duplicates while preserving keys
            $choices += \array_diff($package->getChoices(), $choices);
        }
        return $choices;
    }

    public function getIconMarkup($iconCode, $additionalClass = '')
    {
        foreach ($this->packages as $package) {
            if ($package->isHandled($iconCode)) {
                $markup = $package->getIconMarkup($iconCode, $additionalClass);
                if (!$markup) {
                    throw new \LogicException("Icon package " . $package::class . " produced no markup for {$iconCode}");
                }
                return $markup;
            }
        }
        // Fingers crossed
        return HtmlUtil::renderTag('span', '', [
            'class' => 'mb-glyphicon ' . $iconCode . ' ' . $additionalClass,
        ]);
    }

    public function getStyleSheets(): array
    {
        $styleSheets = [];
        foreach ($this->packages as $package) {
            $styleSheets = \array_merge($styleSheets, $package->getStyleSheets());
        }
        return \array_values(\array_unique($styleSheets));
    }

    public function isHandled($iconCode): bool
    {
        return true;
    }

    public function getAliases(): never
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
