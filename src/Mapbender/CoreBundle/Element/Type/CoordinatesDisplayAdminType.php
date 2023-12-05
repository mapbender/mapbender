<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class CoordinatesDisplayAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numDigits', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => true,
                'label' => 'mb.core.coordinatesdesplay.admin.numdigits',
            ))
            ->add('label', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.button.show_label',
            ))
            ->add('empty', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinatesdesplay.admin.empty',
            ))
            ->add('prefix', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinatesdesplay.admin.prefix',
            ))
            ->add('separator', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinatesdesplay.admin.separator',
            ))
        ;
    }

}
