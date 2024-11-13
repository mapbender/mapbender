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
class SearchAdminType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('driver', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.shDriver',
                #'placeholder' => 'mb.routing.backend.dialog.label.chooseOption',
                'required' => true,
                'choices' => [
                    'Solr' => 'solr',
                    # 'PostgreSQL' => 'sql',
                    # 'Nominatim' => 'nominatim',
                ],
            ])
            ->add('url', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.shUrl',
                'required' => true,
                'property_path' => '[solr][url]',
            ])
            ->add('query_key', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.query_key',
                'empty_data' => 'q',
                'required' => true,
                'property_path' => '[solr][query_key]',
            ])
            ->add('query_ws_replace', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.query_ws_replace', #'Query Whitespace replacement pattern',
                'empty_data' => '',
                'trim' => false,
                'required' => false,
                'property_path' => '[solr][query_ws_replace]',
            ])
            ->add('query_format', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.query_format', #'Query key format',
                'empty_data' => '%s',
                'property_path' => '[solr][query_format]',
                'required' => false,
            ])
            ->add('token_regex', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.token_regex', #'Token (JavaScript regex)',
                'empty_data' => null, #'[^a-zA-Z0-9äöüÄÖÜß]',
                'property_path' => '[solr][token_regex]',
                'required' => false,
            ])
            ->add('token_regex_in', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.token_regex_in', #'Token search (JavaScript regex)',
                'empty_data' => null, #'([a-zA-ZäöüÄÖÜß]{3,})',
                'property_path' => '[solr][token_regex_in]',
                'required' => false,
            ])
            ->add('token_regex_out', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.token_regex_out', #'Token replace (JavaScript regex)',
                'empty_data' => null, #'$1*',
                'property_path' => '[solr][token_regex_out]',
                'required' => false,
            ])
            ->add('collection_path', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.collection_path',
                'property_path' => '[solr][collection_path]',
                'empty_data' => 'response.docs',
                'required' => false,
            ])
            ->add('label_attribute', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.label_attribute',
                'property_path' => '[solr][label_attribute]',
                'empty_data' => 'label',
                'required' => true,
            ])
            ->add('geom_attribute', TextType::class, [
                'label' => "mb.routing.backend.dialog.label.sh.geom_attribute",
                'property_path' => '[solr][geom_attribute]',
                'empty_data' => 'geom',
                'required' => true,
            ])
            ->add('geom_format', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.geom_format',
                'property_path' => '[solr][geom_format]',
                'empty_data' => 'WKT',
                'required' => true,
                'choices' => [
                    'WKT' => 'WKT',
                    'GeoJSON' => 'GeoJSON',
                ],
            ])
            ->add('geom_proj', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.geom_proj',
                'property_path' => '[solr][geom_proj]',
                'required' => true,
            ])
            ->add('delay', NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.sh.delay',
                'property_path' => '[solr][delay]',
                'empty_data' => '300',
                'required' => false,
            ])
        ;

        # Database SQL Search
        $builder
            ->add('connection', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.connectionSearchName',
                'required' => false,
                'empty_data' => 'search_db',
                'property_path' => '[sql][connection]',
            ])
            ->add('table', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchTable',
                'required' => false,
                'empty_data' => 'search_db',
                'property_path' => '[sql][table]',
            ])
            ->add('searchStrColumn', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchStrColumn',
                'required' => false,
                'empty_data' => 'search_db',
                'property_path' => '[sql][searchStrColumn]',
            ])
            ->add('searchAdressColumn', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchAdressColumn',
                'required' => false,
                'empty_data' => 'search_db',
                'property_path' => '[sql][searchAdressColumn]',
            ])
            ->add('searchGeomColumn', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.searchGeomColumn',
                'required' => false,
                'empty_data' => 'search_db',
                'property_path' => '[sql][searchGeomColumn]',
            ])
        ;
    }
}
