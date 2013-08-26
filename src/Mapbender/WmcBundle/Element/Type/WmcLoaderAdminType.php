<?php

namespace Mapbender\WmcBundle\Element\Type;

use FOM\UserBundle\Form\DataTransformer\GroupIdTransformer;
use Mapbender\WmcBundle\Form\EventListener\WmcHandlerFieldSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of WmcEditorAdminType
 *
 * @author Paul Schmidt
 */
class WmcLoaderAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmcloader';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application'   => $options['application'],
                'property_path' => '[target]',
                'required'      => false))
            ->add('keepSources', 'choice',
                array(
                'required'    => false,
                'choices'     => array(
                    "no" => " no ",
                    "basesources" => "BaseSources",
                    "allsources"  => "AllSources")))
            ->add('keepExtent', 'checkbox',
                array(
                'required' => false));
    }

}

?>
