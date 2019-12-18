<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class SourceInstanceType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'source_instance';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
            ->add('basesource', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.basesource',
            ))
            ->add('proxy', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.proxy',
            ))
            ->add('opacity', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'attr' => array(
                    'min' => 0,
                    'max' => 100,
                ),
                'constraints' => array(
                    new Constraints\Range(array(
                        'min' => 0,
                        'max' => 100,
                    )),
                ),
                'required' => false,
            ))
        ;
    }
}
