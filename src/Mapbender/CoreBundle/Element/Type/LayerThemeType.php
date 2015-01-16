<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\DataTransformer\LayertreeThemeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
//use Symfony\Component\Form\FormView;
//use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LayerThemeType extends AbstractType
{

    public function getName()
    {
        return 'theme';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'id' => null,
            'title' => '',
            'opened' => true
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder->add('id', 'hidden', array('required' => true, 'property_path' => '[id]'))
                ->add('title', 'hidden', array('required' => false, 'property_path' => '[title]'))
                ->add('opened', 'checkbox', array('required' => false, 'property_path' => '[opened]'));
    }

}
