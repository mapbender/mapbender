<?php


namespace Mapbender\WmsBundle\Element\Type;


use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UtfGridInfoAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'utfgridinfo';
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
        $builder->add('target', 'target_element', array(
            'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
            'application' => $options['application'],
            'property_path' => '[target]',
            'required' => true,
        ));
        // deactivated for now
        if (false) $builder->add('labelFormats', new YAMLConfigurationType(), array(
            'label' => 'mb.wms.admin.utfgridinfo.label.labelFormats',
        ));
    }
}
