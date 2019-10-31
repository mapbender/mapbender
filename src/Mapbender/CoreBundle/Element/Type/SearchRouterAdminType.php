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
                'required' => false,
            ))
            ->add('dialog', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                // @todo: fix legacy config errors anywhere else, but not on the form level
                'property_path' => '[asDialog]',
            ))
            ->add('timeout', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'label' => 'Timeout factor',
                // @todo: fix legacy config errors anywhere else, but not on the form level
                'property_path' => '[timeoutFactor]',
            ))
            ->add('width', 'Symfony\Component\Form\Extension\Core\Type\IntegerType')
            ->add('height', 'Symfony\Component\Form\Extension\Core\Type\IntegerType')
            ->add('routes', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'entry_type' => 'Mapbender\CoreBundle\Element\Type\SearchRouterRouteAdminType',
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
            ))
        ;
    }

}
