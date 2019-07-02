<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class OnlineResourceType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'onlineresource';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('format', 'text',
                      array(
                    'required' => false,))
                ->add('href', 'text',
                      array(
                    'required' => false,));
    }

}

