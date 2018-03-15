<?php
namespace Mapbender\WmtsBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of WmtsLoaderAdminType
 *
 * @author Paul Schmidt
 */
class WmtsLoaderAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmtsloader';
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
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('defaultFormat', 'choice',
                array(
                "choices" => array(
                    "image/png" => "image/png",
                    "image/gif" => "image/gif",
                    "image/jpeg" => "image/jpeg")))
            ->add('defaultInfoFormat', 'choice',
                array(
                "choices" => array(
                    "text/html" => "text/html",
                    "text/xml" => "text/xml",
                    "text/plain" => "text/plain")))
            ->add('autoOpen', 'checkbox', array('required' => false))
            ->add('splitLayers', 'checkbox', array('required' => false))
            ->add('useDeclarative', 'checkbox', array('required' => false));
    }

}
?>
