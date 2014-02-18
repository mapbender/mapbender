<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\CoreBundle\Element\Type\SearchRouterRouteAdminType;


class SimpleSearchAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'simplesearch_form';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('query_url', 'text', array(
                'label' => 'Query URL',
                'property_path' => '[query_url]',
                'required' => true))
            ->add('query_key', 'text', array(
                'label' => 'Query URL key',
                'property_path' => '[query_key]',
                'required' => true))
            ->add('query_format', 'text', array(
                'label' => 'Query key format',
                'property_path' => '[query_format]',
                'required' => true))
            ->add('collection_path', 'text', array(
                'property_path' => '[collection_path]',
                'required' => false))
            ->add('label_attribute', 'text', array(
                'property_path' => '[label_attribute]',
                'required' => true))
            ->add('geom_attribute', 'text', array(
                'property_path' => '[geom_attribute]',
                'required' => true))
            ->add('geom_format', 'choice', array(
                'property_path' => '[geom_format]',
                'choices' => array(
                    'WKT' => 'WKT',
                    'GeoJSON' => 'GeoJSON'),
                'required' => true))
            ->add('delay', 'number', array(
                'property_path' => '[delay]',
                'required' => true));
    }
}
