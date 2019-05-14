<?php


namespace Mapbender\CoreBundle\Extension;


class SitelinksExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction('get_sitelinks', array($this, 'get_sitelinks')),
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
