<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetViewAdminType extends AbstractType
{
    public function getParent(): string
    {
        return BaseButtonAdminType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Icon is hard-coded, remove upstream icon field.
        if ($builder->has('icon')) {
            $builder->remove('icon');
        }
        $builder
            ->add('resetDynamicSources', CheckboxType::class, [
                'label' => 'mb.core.resetView.admin.resetDynamicSources',
                'required' => false,
            ])
        ;
    }
}
