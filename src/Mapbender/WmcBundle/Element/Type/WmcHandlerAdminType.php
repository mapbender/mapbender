<?php

namespace Mapbender\WmcBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of WmcEditorAdminType
 *
 * @author Paul Schmidt
 */
class WmcHandlerAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmchandler';
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
                ->add('accessRoles', 'choice',
                        array(
                    'choices'  => array(),
                    'required' => false))
                ->add('keepBaseSources', 'checkbox',
                        array(
                    'required' => false))
                ->add('useEditor', 'checkbox',
                        array(
                    'required' => false))
                ->add('useSuggestMap', 'checkbox',
                        array(
                    'required' => false))
                ->add('receiver', 'choice',
                        array(
                    'multiple' => true,
                    'required' => false,
                    'choices'  => array(
                        'email'    => 'e-mail',
                        'facebook' => 'facebook',
                        'twitter'  => 'twitter')))
                ->add('useLoader', 'checkbox',
                        array(
                    'required' => false))
        ;
    }

}

?>
