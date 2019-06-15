<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
    public function configureOptions(OptionsResolver $resolver)
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
            ->add('query_ws_replace', 'text', array(
                'label' => 'Query Whitespace replacement pattern',
                'trim' => false,
                'property_path' => '[query_ws_replace]'))
            ->add('query_format', 'text', array(
                'label' => 'Query key format',
                'property_path' => '[query_format]',
                'required' => true))
            ->add('token_regex', 'text', array(
                'label' => 'Token (JavaScript regex)',
                'property_path' => '[token_regex]',
                'required' => false))
            ->add('token_regex_in', 'text', array(
                'label' => 'Token search (JavaScript regex)',
                'property_path' => '[token_regex_in]',
                'required' => false))
            ->add('token_regex_out', 'text', array(
                'label' => 'Token replace (JavaScript regex)',
                'property_path' => '[token_regex_out]',
                'required' => false))
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
            ->add('geom_srs', 'number', array(
                'property_path' => '[geom_srs]',
                'required' => true))
            ->add('delay', 'number', array(
                'property_path' => '[delay]',
                'required' => true))
            ->add('result_buffer', 'number', array(
                'property_path' => '[result][buffer]'))
            ->add('result_minscale', 'number', array(
                'property_path' => '[result][minscale]'))
            ->add('result_maxscale', 'number', array(
                'property_path' => '[result][maxscale]'))
            ->add('result_icon_url', 'text', array(
                'property_path' => '[result][icon_url]'))
            ->add('result_icon_offset', 'text', array(
                'property_path' => '[result][icon_offset]'));
    }
}
