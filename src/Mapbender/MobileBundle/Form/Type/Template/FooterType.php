<?php


namespace Mapbender\MobileBundle\Form\Type\Template;


use Symfony\Component\Form\AbstractType;

class FooterType extends AbstractType
{
    public function getParent()
    {
        return 'Mapbender\CoreBundle\Form\Type\Template\BaseToolbarType';
    }
}
