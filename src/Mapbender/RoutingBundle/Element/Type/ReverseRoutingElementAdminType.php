<?php

namespace Mapbender\RoutingBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class RoutingElementAdminType
 * @package Mapbender\RoutingBundle\Element\Type
 * @author Christian Kuntzsch
 * @author Robert Klemm
 */
class ReverseRoutingElementAdminType extends AbstractType
{

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'Reverse_Routing_Element_Admin_Type';
    }

    /**
     * @inheritdoc
     */
/*    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }*/


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        parent::buildForm($builder, $options);

        # reverseGeocoding Config
        $builder
            ->add('revGeocodingDriver', ChoiceType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.reverse.geocodeDriver",
                    'required' => false,
                    'empty_data' => 'sql',
                    'choices' => array(
                        #'nominatim'   => "Nominatim",
                        'sql'         => "PostgreSQL"
                        #'solr'        => "Solr")
                    ),
                    'property_path' => '[revGeocodingDriver]'
                )
            )
            ->add('revGeoConnection', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.reverse.connection",
                    'required' => false,
                    'empty_data' => null,
                    'property_path' => '[revGeoConnection]'
                )
            )
            ->add('revTableName', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.reverse.revTableName",
                    'required' => false,
                    'empty_data' => null,
                    'property_path' => '[revTableName]'
                )
            )
            ->add('revRowGeoWay', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.reverse.revRowGeoWay",
                    'required' => false,
                    'empty_data' => 'the_geom',
                    'property_path' => '[revRowGeoWay]'
                )
            )
            ->add('revRowSearch', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.reverse.revRowSearch",
                    'required' => false,
                    'empty_data' => null,
                    'property_path' => '[revRowSearch]'
                )
            )
            ->add('revSearchBuffer', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.reverse.revRowSearchBuffer",
                    'required' => false,
                    'empty_data' => 50,
                    'attr' => array(
                        'type' => 'number',
                        'min' => 0,
                        ),
                    'property_path' => '[revSearchBuffer]'
                )
            )
        ;
    }
}
