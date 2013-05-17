<?php

namespace Mapbender\WmcBundle\Form\Type;

use Mapbender\CoreBundle\Form\Type\StateType;
use Mapbender\WmsBundle\Form\Type\LegendUrlType;
use Mapbender\WmsBundle\Form\Type\OnlineResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

//use Symfony\Component\Form\FormBuilder;

class WmcType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmc';
    }

//
//    /**
//     * @inheritdoc
//     */
//    public function setDefaultOptions(OptionsResolverInterface $resolver)
//    {
//        $resolver->setDefaults(array());
//    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('state', new StateType(),
                      array(
//                    'property_path' => '[state]',
                    'data_class' => 'Mapbender\CoreBundle\Entity\State'))
                ->add('keywords', 'text',
                      array(
                    'required' => false))
                ->add('abstract', 'textarea')
                ->add('logourl', new LegendUrlType(),
                      array(
//                    'property_path' => '[logourl]',
                    'data_class' => 'Mapbender\WmsBundle\Component\LegendUrl'))
                ->add('descriptionurl', new OnlineResourceType(),
                      array(
//                    'property_path' => '[descriptionurl]',
                    'data_class' => 'Mapbender\WmsBundle\Component\OnlineResource'))
                ->add('screenshot', 'file');
    }

}
