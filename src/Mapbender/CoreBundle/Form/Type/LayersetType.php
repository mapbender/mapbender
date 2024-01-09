<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class LayersetType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'attr' => array(
                    'maxlength' => 128,
                    'label' => 'mb.core.admin.layerset.label.title',
                ),
            ))
            ->add('selected', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.layerset.label.selected',
            ))
        ;
    }
}

