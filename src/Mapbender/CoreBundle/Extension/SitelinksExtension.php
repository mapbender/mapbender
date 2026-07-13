<?php


namespace Mapbender\CoreBundle\Extension;


use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SitelinksExtension extends AbstractExtension
{
    /**
     * @param string[][] $linkConfig
     */
    public function __construct(protected $linkConfig)
    {
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'mapbender_sitelinks';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_sitelinks', $this->get_sitelinks(...)),
        ];
    }

    /**
     * @return string[][]
     */
    public function get_sitelinks()
    {
        return $this->linkConfig;
    }
}
