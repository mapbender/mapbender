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
//            'target' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
                ->add('width', 'text', array('required' => true))->add('anchor', "choice",
                      array(
                    'required' => true,
                    "choices" => array(
                        'inline' => 'inline',
                        'left-top' => 'left-top',
                        'left-bottom' => 'left-bottom',
                        'right-top' => 'right-top',
                        'right-bottom' => 'right-bottom')))
                ->add('position', new PositionType(),
                      array(
                    'label' => 'Position',
                    'property_path' => '[position]'))
                ->add('copyrigh_text', 'text', array('required' => false))
                ->add('dialog_link', 'text', array('required' => false))
                ->add('dialog_content', 'textarea', array('required' => false))
                ->add('dialog_title', 'text', array('required' => false));
    }

}