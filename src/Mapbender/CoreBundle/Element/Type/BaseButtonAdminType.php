<?php


namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BaseButtonAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tooltip', TextType::class, [
                'required' => false,
                'label' => 'mb.core.basebutton.admin.tooltip',
            ])
            ->add('icon', IconClassType::class, [
                'required' => false,
                'label' => 'mb.core.basebutton.admin.icon',
            ])
            ->add('label', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.button.show_label',
            ])
        ;
    }
}
