<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SearchRouterAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'routes' => array(),
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false,
            ))
            ->add('dialog', 'checkbox', array(
                'property_path' => '[asDialog]',
            ))
            ->add('timeout', 'integer', array(
                'label' => 'Timeout factor',
                'property_path' => '[timeoutFactor]',
            ))
            ->add('width', 'integer', array('required' => true))
            ->add('height', 'integer', array('required' => true))
            ->add('routes', 'collection', array(
                'type' => new SearchRouterRouteAdminType(),
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
            ))
        ;
    }

}
