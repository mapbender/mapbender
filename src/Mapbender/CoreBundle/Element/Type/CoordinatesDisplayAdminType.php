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
                'label' => 'mb.core.coordinesdisplay.admin.numdigits',
            ))
            ->add('label', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.coordinesdisplay.admin.label',
            ))
            ->add('empty', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinesdisplay.admin.empty',
            ))
            ->add('prefix', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinesdisplay.admin.prefix',
            ))
            ->add('separator', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'trim' => false,
                'label' => 'mb.core.coordinesdisplay.admin.separator',
            ))
        ;
    }

}
