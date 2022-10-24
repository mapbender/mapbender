<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IconExtension extends AbstractExtension
{
    /** @var AssetExtension */
    protected $assetExtension;
    /** @var IconPackageInterface */
    protected $iconIndex;

    public function __construct(AssetExtension $assetExtension,
                                IconPackageInterface $iconIndex)
    {
        $this->assetExtension = $assetExtension;
        $this->iconIndex = $iconIndex;
    }

    public function getFunctions()
    {
        return array(
            'icon_markup' => new TwigFunction('icon_markup', array($this, 'icon_markup')),
            'icon_stylesheets' => new TwigFunction('icon_stylesheets', array($this, 'icon_stylesheets')),
            'icon_stylesheet_links' => new TwigFunction('icon_stylesheet_links', array($this, 'icon_stylesheet_links')),
        );
    }

    /**
     * @param string $iconCode
     * @return string
     */
    public function icon_markup($iconCode)
    {
        return $this->iconIndex->getIconMarkup($iconCode) ?: '';
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
    public function icon_stylesheet_links()
    {
        $parts = array();
        foreach ($this->iconIndex->getStyleSheets() as $path) {
            $attributes = array(
                'rel' => 'stylesheet',
                'href' => $this->assetExtension->getAssetUrl($path),
            );
            $parts[] = '<link ' . HtmlUtil::renderAttributes($attributes) . ' />';
        }
        return \implode('', $parts);
    }
}
