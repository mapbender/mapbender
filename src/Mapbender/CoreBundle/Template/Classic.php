<?php

namespace Mapbender\CoreBundle\Template;

/**
 * Template Classic
 */
class Classic extends Fullscreen
{
    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Classic template';
    }

    /**
     * {@inheritdoc}
     */
    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderCoreBundle/Resources/public/sass/template/classic.scss',
                );
            case 'js':
            case 'trans':
            default:
                return parent::getAssets($type);
        }
    }

    public function getTwigTemplate()
    {
        return 'MapbenderCoreBundle:Template:classic.html.twig';
    }
}
