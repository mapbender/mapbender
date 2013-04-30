<?php

namespace Mapbender\WmsBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of WmsLoaderAdminType
 *
 * @author Paul Schmidt
 */
class WmsLoaderAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmsloader';
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
                ->add('defaultformat', 'choice',array(
                    "choices" => array(
                        "png" => "image/png",
                        "gif" => "image/gif",
                        "jpeg" => "image/jpeg")))
                ->add('defaultinfoformat', 'choice',array(
                    "choices" => array(
                        "html" => "text/html",
                        "xml" => "text/xml",
                        "plain" => "text/plain")))
                ->add('autoOpen', 'checkbox', array('required' => false));
//                ->add('toc', 'text', array('required' => false));
    }

}

?>
