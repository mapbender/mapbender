<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class CoordinatesDisplayAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numDigits', IntegerType::class, [
                'required' => true,
                'label' => 'mb.core.coordinesdisplay.admin.numdigits',
            ])
            ->add('label', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.coordinesdisplay.admin.label',
            ])
            ->add('empty', TextType::class, [
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinesdisplay.admin.empty',
            ])
            ->add('prefix', TextType::class, [
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinesdisplay.admin.prefix',
            ])
            ->add('separator', TextType::class, [
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinesdisplay.admin.separator',
            ])
        ;
    }

}
