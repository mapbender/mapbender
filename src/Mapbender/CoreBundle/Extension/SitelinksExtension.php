<?php


namespace Mapbender\CoreBundle\Extension;


use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SitelinksExtension extends AbstractExtension
{
    /** @var string[][] */
    protected $linkConfig;

    /**
     * @param string[][] $linkConfig
     */
    public function __construct($linkConfig)
    {
        $this->linkConfig = $linkConfig;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'mapbender_sitelinks';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('get_sitelinks', array($this, 'get_sitelinks')),
        );
    }

    /**
     * @return string[][]
     */
    public function get_sitelinks()
    {
        return $this->linkConfig;
    }
}
