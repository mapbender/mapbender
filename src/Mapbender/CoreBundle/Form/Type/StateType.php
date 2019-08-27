<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StateType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'state';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'label_attr' => array(
                'class' => 'hidden',
            ),
            'compound' => true,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add("id", "hidden", array("required" => false))
                ->add("slug", "hidden", array("required" => true))
                ->add("json", "hidden", array("required" => true))
                ->add("title", "text", array("required" => true));
    }
}

