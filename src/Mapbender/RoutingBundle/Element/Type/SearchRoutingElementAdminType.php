<?php

namespace Mapbender\RoutingBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class RoutingElementAdminType
 * @package Mapbender\RoutingBundle\Element\Type
 * @author Christian Kuntzsch
 * @author Robert Klemm
 */
class SearchRoutingElementAdminType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('searchDriver', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.shDriver',
                'required' => false,
                'choices' => [
                    # 'Nominatim' => 'nominatim',
                    # 'PostgreSQL' => 'sql',
                    'Solr' => 'solr',
                ],
            ])
            ->add('searchUrl', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.shUrl',
                'required' => true,
            ])
            ->add('query_key', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.query_key',
                'empty_data' => 'q',
                'required' => false,
            ])
            ->add('query_ws_replace', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.query_ws_replace', #'Query Whitespace replacement pattern',
                'empty_data' => '',
                'trim' => false,
                'required' => false,
                'property_path' => '[query_ws_replace]',
            ])
            ->add('query_format', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.query_format', #'Query key format',
                'empty_data' => '%s',
                'property_path' => '[query_format]',
                'required' => false,
            ])
            ->add('token_regex', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.token_regex', #'Token (JavaScript regex)',
                'empty_data' => null, #'[^a-zA-Z0-9äöüÄÖÜß]',
                'property_path' => '[token_regex]',
                'required' => false,
            ])
            ->add('token_regex_in', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.token_regex_in', #'Token search (JavaScript regex)',
                'empty_data' => null, #'([a-zA-ZäöüÄÖÜß]{3,})',
                'property_path' => '[token_regex_in]',
                'required' => false,
            ])
            ->add('token_regex_out', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.token_regex_out', #'Token replace (JavaScript regex)',
                'empty_data' => null, #'$1*',
                'property_path' => '[token_regex_out]',
                'required' => false,
            ])
            ->add('collection_path', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.collection_path',
                'property_path' => '[collection_path]',
                'empty_data' => 'response.docs',
                'required' => false,
            ])
            ->add('label_attribute', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.label_attribute',
                'property_path' => '[label_attribute]',
                'empty_data' => 'label',
                'required' => false,
            ])
            ->add('geom_attribute', TextType::class, [
                'label' => "mb.routing.backend.dialog.label.sh.geom_attribute",
                'property_path' => '[geom_attribute]',
                'empty_data' => 'geom',
                'required' => false,
            ])
            ->add('geom_format', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.geom_format',
                'property_path' => '[geom_format]',
                'empty_data' => 'WKT',
                'required' => false,
                'choices' => [
                    'WKT' => 'WKT',
                    'GeoJSON' => 'GeoJSON',
                    'CSV' => 'CSV',
                ],
            ])
            ->add('delay', NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.delay',
                'property_path' => '[delay]',
                'empty_data' => '300',
                'required' => false,
            ])
            ->add('result_buffer', NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.result_buffer',
                'property_path' => '[result][buffer]',
                'empty_data' => '0',
                'required' => false,
            ])
            ->add('result_minscale', NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.result_minscale',
                'property_path' => '[result][minscale]',
                'required' => false,
            ])
            ->add('result_maxscale', NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.result_maxscale',
                'property_path' => '[result][maxscale]',
                'required' => false,
            ])
            ->add('result_icon_url', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.result_icon_url',
                'property_path' => '[result][icon_url]',
                'required' => false,
            ])
            ->add('result_icon_offset', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.result_icon_offset',
                'property_path' => '[result][icon_offset]',
                'required' => false,
            ]);
        ;

        # Search-Settings
        $builder
            ->add('searchDriver', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchDriver',
            ])
            ->add('connectionSearchName', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.connectionSearchName',
                'required' => false,
                'empty_data' => 'search_db',
                'attr' => [
                    'advanced' => 'false',
                ],
            ])
            ->add('searchTable', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchTable',
                'required' => false,
                'empty_data' => 'search_db',
                'attr' => [
                    'advanced' => 'false',
                ],
            ])
            ->add('searchStrColumn', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchStrColumn',
                'required' => false,
                'empty_data' => 'search_db',
                'attr' => [
                    'advanced' => 'false',
                ],
            ])
            ->add('searchAdressColumn', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchAdressColumn',
                'required' => false,
                'empty_data' => 'search_db',
                'attr' => [
                    'advanced' => 'false',
                ],
            ])
            ->add('searchGeomColumn', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchGeomColumn',
                'required' => false,
                'empty_data' => 'search_db',
                'attr' => [
                    'advanced' => 'false',
                ],
            ])
        ;
    }
}
