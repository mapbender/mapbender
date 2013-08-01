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
                ->add('keepBaseSources', 'checkbox',
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
                ->add('accessLoaderAnonymous', 'checkbox',
                        array(
                    'required' => false))
                ->add('accessGroupsLoader', 'fom_groups',
                        array(
                    'return_entity' => false,
                    'user_groups'   => false,
                    'property_path' => '[accessGroupsLoader]',
                    'required'      => false,
                    'multiple'      => true,
                    'empty_value' => 'Choose an option',
                            ))
                ->add('useEditor', 'checkbox',
                        array(
                    'required' => false))
                ->add('accessEditorAnonymous', 'checkbox',
                        array(
                    'required' => false))
                ->add('accessGroupsEditor', 'fom_groups',
                        array(
                    'return_entity' => false,
                    'user_groups'   => false,
                    'property_path' => '[accessGroupsEditor]',
                    'required'      => false,
                    'multiple'      => true,
                    'empty_value' => 'Choose an option',
                            ))
        ;
        $subscriber = new WmcHandlerFieldSubscriber($builder->getFormFactory());
        $builder->addEventSubscriber($subscriber);
    }

}

?>
