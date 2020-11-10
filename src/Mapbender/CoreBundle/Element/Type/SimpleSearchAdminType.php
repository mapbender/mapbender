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
            ->add('query_url', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Query URL',
                'required' => true,
            ))
            ->add('query_key', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Query URL key',
                'required' => true,
            ))
            ->add('query_ws_replace', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Query Whitespace replacement pattern',
                'trim' => false,
            ))
            ->add('query_format', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Query key format',
                'required' => true,
            ))
            ->add('token_regex', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Token (JavaScript regex)',
                'required' => false,
            ))
            ->add('token_regex_in', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Token search (JavaScript regex)',
                'required' => false,
            ))
            ->add('token_regex_out', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Token replace (JavaScript regex)',
                'required' => false,
            ))
            ->add('collection_path', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('label_attribute', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
            ->add('geom_attribute', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
            ->add('geom_format', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => array(
                    'WKT' => 'WKT',
                    'GeoJSON' => 'GeoJSON',
                ),
                'choices_as_values' => true,
                'required' => true,
            ))
            ->add('delay', 'Symfony\Component\Form\Extension\Core\Type\NumberType', array(
                'required' => true,
            ))
            ->add('result_buffer', 'Symfony\Component\Form\Extension\Core\Type\NumberType', array(
                'property_path' => '[result][buffer]',
            ))
            ->add('result_minscale', 'Symfony\Component\Form\Extension\Core\Type\NumberType', array(
                'property_path' => '[result][minscale]',
            ))
            ->add('result_maxscale', 'Symfony\Component\Form\Extension\Core\Type\NumberType', array(
                'property_path' => '[result][maxscale]',
            ))
            ->add('result_icon_url', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'property_path' => '[result][icon_url]',
            ))
            ->add('result_icon_offset', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'property_path' => '[result][icon_offset]',
            ))
        ;
    }
}
