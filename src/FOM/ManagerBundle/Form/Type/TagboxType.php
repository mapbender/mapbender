<?php

namespace FOM\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class TagboxType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'tagbox';
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\CheckboxType';
    }
}
