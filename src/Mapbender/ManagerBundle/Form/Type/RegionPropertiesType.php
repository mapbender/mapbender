<?php
namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Mapbender\ManagerBundle\Form\EventListener\RegionPropertiesSubscriber;

class RegionPropertiesType extends AbstractType
{

    public function getName()
    {
        return 'regionproperties';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'available_properties' => array()));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new RegionPropertiesSubscriber($builder->getFormFactory(), $options);
        $builder->addEventSubscriber($subscriber);
        $available_properties = $options['available_properties'];
        $builder->add('name', 'hidden')
//            ->add('properties', 'choice',
//                array(
//                'expanded' => true,
//                'multiple' => true,
//                'choices' => array("tabs"=> "tabs","blabla" => "blabla"),
//            ))
        ;
    }

}
