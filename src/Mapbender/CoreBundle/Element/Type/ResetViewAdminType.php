<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetViewAdminType extends AbstractType
{
    public function getParent()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseButtonAdminType';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Icon is hard-coded, remove upstream icon field.
        if ($builder->has('icon')) {
            $builder->remove('icon');
        }
        $builder
            ->add('resetDynamicSources', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'label' => 'mb.core.resetView.admin.resetDynamicSources',
                'required' => false,
            ))
        ;
    }
}
