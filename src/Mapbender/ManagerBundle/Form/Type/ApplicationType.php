<?php
namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\ManagerBundle\Form\EventListener\RegionSubscriber;

class ApplicationType extends AbstractType
{

    public function getName()
    {
        return 'application';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'available_templates' => array(),
            'available_properties' => array()
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

//        $subscriber = new RegionSubscriber($builder->getFormFactory(), $options);
//        $builder->addEventSubscriber($subscriber);
        $builder
            // Base data
            ->add('title', 'text',
                array(
                'attr' => array(
                    'title' => 'The application title, as shown in the browser '
                    . 'title bar and in lists.')))
            ->add('slug', 'text',
                array(
                'label' => 'URL title',
                'attr' => array(
                    'title' => 'The URL title (slug) is based on the title and used in the '
                    . 'application URL.')))
            ->add('description', 'textarea',
                array(
                'required' => false,
                'attr' => array(
                    'title' => 'The description is used in overview lists.')))
            ->add('template', 'choice',
                array(
                'choices' => $options['available_templates'],
                'attr' => array(
                    'title' => 'The HTML template used for this '
                    . 'application.')))
            ->add('regionProperties', 'collection',
                array(
                'type' => new RegionPropertiesType(),
                'options' => array(
                    'data_class' => 'Mapbender\CoreBundle\Entity\RegionProperties',
                    'available_properties' => $options['available_properties']
                )
//                'choices' => $options['available_templates'],
            ))
            ->add('custom_css', 'textarea', array(
                'required' => false))

            // Security
            ->add('published', 'checkbox',
                array(
                'required' => false,
                'label' => 'Published'));

        $builder->add('acl', 'acl',
            array(
            'property_path' => false,
            'data' => $options['data'],
            'permissions' => 'standard::object'));
    }

}
