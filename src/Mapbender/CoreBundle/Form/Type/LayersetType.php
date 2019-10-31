<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LayersetType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'layerset';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add("id", "hidden", array("required" => false))
            ->add("title", "text", array(
                'attr' => array(
                    'maxlength' => 128,
                ),
            ))
        ;
    }

}

