<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ShareUrlAdminType extends AbstractType
{
    public function getParent(): string
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseButtonAdminType';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Icon is hard-coded, remove upstream icon field.
        if ($builder->has('icon')) {
            $builder->remove('icon');
        }
    }
}
