<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\CoreBundle\Form\Type\PositionType;

/**
 * 
 */
class CopyrightAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'copyright';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
                ->add('width', 'text', array('required' => true))
                ->add('anchor', "choice",
                      array(
                    'required' => true,
                    "choices" => array(
                        'left-top' => 'left-top',
                        'left-bottom' => 'left-bottom',
                        'right-top' => 'right-top',
                        'right-bottom' => 'right-bottom')))
                ->add('copyright_text', 'text', array('required' => false))
                ->add('copyright_link', 'text', array('required' => false))
                ->add('link_type', 'choice', array(
                    'choices' => array('' => ' ','dialog' => 'dialog', 'url' => 'url'),
                    'required' => false))
                ->add('link_url', 'text', array('required' => false))
                ->add('dialog_content', 'textarea', array('required' => false))
                ->add('dialog_title', 'text', array('required' => false));
    }

}