<?php

namespace FOM\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class TagboxType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'tagbox';
    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }
}
