<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SimpleSearchAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('query_url', 'text', array(
                'label' => 'Query URL',
                'required' => true,
            ))
            ->add('query_key', 'text', array(
                'label' => 'Query URL key',
                'required' => true,
            ))
            ->add('query_ws_replace', 'text', array(
                'label' => 'Query Whitespace replacement pattern',
                'trim' => false,
            ))
            ->add('query_format', 'text', array(
                'label' => 'Query key format',
                'required' => true,
            ))
            ->add('token_regex', 'text', array(
                'label' => 'Token (JavaScript regex)',
                'required' => false,
            ))
            ->add('token_regex_in', 'text', array(
                'label' => 'Token search (JavaScript regex)',
                'required' => false,
            ))
            ->add('token_regex_out', 'text', array(
                'label' => 'Token replace (JavaScript regex)',
                'required' => false,
            ))
            ->add('collection_path', 'text', array(
                'required' => false,
            ))
            ->add('label_attribute', 'text', array(
                'required' => true,
            ))
            ->add('geom_attribute', 'text', array(
                'required' => true,
            ))
            ->add('geom_format', 'choice', array(
                'choices' => array(
                    'WKT' => 'WKT',
                    'GeoJSON' => 'GeoJSON',
                ),
                'choices_as_values' => true,
                'required' => true,
            ))
            ->add('delay', 'number', array(
                'required' => true,
            ))
            ->add('result_buffer', 'number', array(
                'property_path' => '[result][buffer]',
            ))
            ->add('result_minscale', 'number', array(
                'property_path' => '[result][minscale]',
            ))
            ->add('result_maxscale', 'number', array(
                'property_path' => '[result][maxscale]',
            ))
            ->add('result_icon_url', 'text', array(
                'property_path' => '[result][icon_url]',
            ))
            ->add('result_icon_offset', 'text', array(
                'property_path' => '[result][icon_offset]',
            ))
        ;
    }
}
