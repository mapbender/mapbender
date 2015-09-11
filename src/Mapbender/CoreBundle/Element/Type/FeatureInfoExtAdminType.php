<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            ->add('highlightSource', "checkbox", array(
                'property_path'   => '[highlightsource]'))
            ->add('loadWms', "checkbox", array(
                'property_path'   => '[loadwms]'));
    }
}
