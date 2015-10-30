<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\CoreBundle\Form\Type\PaintType;

/**
 *
 */
class FeatureInfoExtAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'featureinfoext';
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
        $builder
            ->add('map', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application'   => $options['application'],
                'property_path' => '[map]',
                'required'      => false))
            ->add('featureinfo', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\FeatureInfo',
                'application'   => $options['application'],
                'property_path' => '[featureinfo]',
                'required'      => false))
            ->add('load_declarative_wms', "checkbox", array())
            ->add('highlight_source', "checkbox", array())
            ->add('hits_style', new PaintType(), array('property_path' => '[hits_style]'))
            ->add('hover_style', new PaintType(), array('property_path' => '[hover_style]'));
    }
}
