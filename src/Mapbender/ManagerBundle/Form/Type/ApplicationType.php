<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\ManagerBundle\Form\EventListener\RegionSubscriber;
use Mapbender\ManagerBundle\Form\EventListener\RegionPropertiesSubscriber;

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
//        $subscriber = new RegionPropertiesSubscriber($builder->getFormFactory(), $options);
//        $builder->addEventSubscriber($subscriber);
        $builder
            // Base data
            ->add('title', 'text', array(
                'attr' => array(
                    'title' => 'The application title, as shown in the browser '
                    . 'title bar and in lists.')))
            ->add('slug', 'text', array(
                'label' => 'URL title',
                'attr' => array(
                    'title' => 'The URL title (slug) is based on the title and used in the '
                    . 'application URL.')))
            ->add('description', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'title' => 'The description is used in overview lists.')))
            ->add('template', 'choice', array(
                'choices' => $options['available_templates'],
                'attr' => array(
                    'title' => 'The HTML template used for this application.')))
            ->add('published', 'checkbox', array(
                'required' => false,
                'label' => 'Published'));
        $app = $options['data'];
        foreach ($options['available_properties'] as $region => $properties) {
            $data = "";
            foreach ($app->getRegionProperties() as $key => $regProps) {
                if ($regProps->getName() === $region) {
                    $help = $regProps->getProperties();
                    if (array_key_exists('name', $help)) {
                        $data = $help['name'];
                    }
                }
            }
            $choices = array();
            foreach ($properties as $values) {
                $choices[$values['name']] = $values['label'];
            }
            $builder->add($region, 'choice', array(
                'property_path' => '[' . $region . ']',
                'required' => false,
                'mapped' => false,
                'expanded' => true,
                'data' => $data,
                'choices' => $choices
            ));
        }

        // Security
        $builder->add('acl', 'acl', array(
            'mapped' => false,
            'data' => $options['data'],
            'permissions' => 'standard::object'));
    }

}
