<?php


namespace Mapbender\CoreBundle\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrandingExtension extends AbstractExtension
{
    /** @var string */
    protected $loginBackdrop;
    /** @var string|null */
    protected $loginBackdropHq;

    /**
     * @param string|null $loginBackdrop
     */
    public function __construct($loginBackdrop)
    {
        $this->loginBackdrop = $loginBackdrop ?: 'bundles/mapbendercore/image/login-backdrop.jpg';
        $hqBackdrop = preg_replace('#(\.\w+)$#', '-4k${1}', $this->loginBackdrop);
        // NOTE: assumes cwd == docroot
        if (@\is_file($hqBackdrop) && @\is_readable($hqBackdrop)) {
            $this->loginBackdropHq = $hqBackdrop;
        }
    }

    public function getFunctions()
    {
        return array(
            'login_backdrop_asset' => new TwigFunction('login_backdrop_asset', array($this, 'login_backdrop_asset')),
            'login_backdrop_asset_hq' => new TwigFunction('login_backdrop_asset_hq', array($this, 'login_backdrop_asset_hq')),
        );
    }

    public function login_backdrop_asset()
    {
        return $this->loginBackdrop;
    }

    public function login_backdrop_asset_hq()
    {
        return $this->loginBackdropHq;
    }
}
