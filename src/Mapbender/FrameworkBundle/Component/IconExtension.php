<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IconExtension extends AbstractExtension
{
    public function __construct(protected AssetExtension $assetExtension, protected IconPackageInterface $iconIndex)
    {
    }

    public function getFunctions(): array
    {
        return [
            'icon_markup' => new TwigFunction('icon_markup', $this->icon_markup(...)),
            'icon_stylesheets' => new TwigFunction('icon_stylesheets', $this->icon_stylesheets(...)),
            'icon_stylesheet_links' => new TwigFunction('icon_stylesheet_links', $this->icon_stylesheet_links(...)),
        ];
    }

    public function icon_markup(string $iconCode, ?string $additionalClass = null): string
    {
        $additionalClass = empty($additionalClass) ? 'mb-icon' : $additionalClass . ' mb-icon';
        return $this->iconIndex->getIconMarkup($iconCode, $additionalClass) ?: '';
    }

    /**
     * @return string[]
     */
    public function icon_stylesheets()
    {
        return $this->iconIndex->getStyleSheets();
    }

    /**
     * Emits <link rel="stylesheet" ...> links for all icon packages
     * Should be piped through twig "raw" filter
     *
     * @return string
     */
    public function icon_stylesheet_links(): string
    {
        $parts = [];
        foreach ($this->iconIndex->getStyleSheets() as $path) {
            $attributes = [
                'rel' => 'stylesheet',
                'href' => $this->assetExtension->getAssetUrl($path),
            ];
            $parts[] = '<link ' . HtmlUtil::renderAttributes($attributes) . ' />';
        }
        return \implode('', $parts);
    }
}
