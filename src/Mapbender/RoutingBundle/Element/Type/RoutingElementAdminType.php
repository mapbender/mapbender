<?php

namespace Mapbender\RoutingBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;

/**
 * Class RoutingElementAdminType
 * @package Mapbender\RoutingBundle\Element\Type
 * @author Christian Kuntzsch
 * @author Robert Klemm
 */
class RoutingElementAdminType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('advanced', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.routing.backend.dialog.label.advanced',
            ])
            ->add('autoSubmit', CheckboxType::class, [
                'required' => false,
                'property_path' => '[autoSubmit]',
                'label' => 'mb.routing.backend.dialog.label.autoSubmit',
            ])
            ->add('addIntermediatePoints', CheckboxType::class, [
                'required' => false,
                'property_path' => '[addIntermediatePoints]',
                'label' => 'mb.routing.backend.dialog.label.addIntermediatePoints',
            ])
            ->add('disableContextMenu', CheckboxType::class, [
                'required' => false,
                'property_path' => '[disableContextMenu]',
                'label' => 'mb.routing.backend.dialog.label.disableContextMenu',
            ])
            ->add('addSearch', CheckboxType::class, [
                'required' => false,
                'property_path' => '[addSearch]',
                'label' => 'mb.routing.backend.dialog.label.search',
            ])
            ->add('addReverseGeocoding', CheckboxType::class, [
                'required' => false,
                'property_path' => '[addReverseGeocoding]',
                'label' => 'mb.routing.backend.dialog.label.reverseGeocoding',
            ])
            ->add('buffer', NumberType::class, [
                'label' => "mb.routing.backend.dialog.label.buffer",
                'empty_data' => 0,
                'invalid_message' => 'mb.routing.backend.dialog.error.buffer',
                'error_bubbling' => true,
                'attr' => [
                    'type' => 'number',
                    'min' => 0,
                ],
                'property_path' => '[buffer]',
            ])
            ->add('color', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.color',
                'required' => false,
                'attr' => [
                    'type' => 'color',
                ],
                'empty_data' => '#4286F4',
                'property_path' => '[color]',
            ])
            ->add('width', NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.width',
                'required' => false,
                'empty_data' => 3,
                'attr' => [
                    'type' => 'number',
                    'min' => 0,
                ],
                'property_path' => '[width]',
            ])
            ->add('opacity', RangeType::class, [
                'label' => 'mb.routing.backend.dialog.label.opacity',
                'required' => false,
                'empty_data' => 1,
                'attr' => array(
                    'type' => 'number',
                    'step' => 0.1,
                    'min' => 0,
                    'max' => 1),
                'property_path' => '[opacity]',
            ])
            ->add('startImagePath', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.startImagePath',
                'empty_data' => null,
                'required' => false,
                'property_path' => '[styleMap][start][startImagePath]',
            ])
            ->add('startImageSize', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.startImageSize',
                'required' => false,
                'property_path' => '[styleMap][start][startImageSize]'
            ])
            ->add('startImageOffset', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.startImageOffset',
                'required' => false,
                'property_path' => '[styleMap][start][startImageOffset]',
            ])
            ->add('intermediateImagePath', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.intermediateImagePath',
                'empty_data' => null,
                'required' => false,
                'property_path' => '[styleMap][intermediate][intermediateImagePath]',
            ])
            ->add('intermediateImageSize', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.intermediateImageSize',
                'required' => false,
                'property_path' => '[styleMap][intermediate][intermediateImageSize]',
            ])
            ->add('intermediateImageOffset', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.intermediateImageOffset',
                'required' => false,
                'property_path' => '[styleMap][intermediate][intermediateImageOffset]',
            ])
            ->add('destinationImagePath', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.destinationImagePath',
                'empty_data' => null,
                'required' => false,
                'property_path' => '[styleMap][destination][destinationImagePath]',
            ])
            ->add('destinationImageSize', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.destinationImageSize',
                'required' => false,
                'property_path' => '[styleMap][destination][destinationImageSize]',
            ])
            ->add('destinationImageOffset', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.destinationImageOffset',
                'required' => false,
                'property_path' => '[styleMap][destination][destinationImageOffset]',
            ])
            ->add('infoText', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.infoText',
                'required' => false,
                'empty_data' => "{start} → {destination} </br> {length} will take {time}",
            ])
            ->add('routingDriver', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.routingDriver',
                'placeholder' => 'mb.routing.backend.dialog.label.chooseOption',
                #'empty_data' => 'graphhopper',
                'required' => true,
                'choices' => [
                    'mb.routing.backend.dialog.label.gh.titel' => 'graphhopper',
                    'mb.routing.backend.dialog.label.osrm.titel' => 'osrm',
                    'mb.routing.backend.dialog.label.pg.titel' => 'pgrouting',
                    'Trias' => 'trias',
                ],
            ])
            ->add(
                $builder->create('search', SearchRoutingElementAdminType::class)
            )
            ->add(
                $builder->create('reverse',ReverseRoutingElementAdminType::class)
            )
        ;

        # PgRouting
        $builder
            ->add('connection', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.pg.connection',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][pgrouting][connection]',
            ])
            ->add('pgWayTable', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.pg.pgWayTable',
                'required' => false,
                'empty_data' => 'routing',
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][pgrouting][wayTable]',
            ])
            ->add('pgWayTableVertices', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.pg.pgWayTableVertices',
                'required' => false,
                'empty_data' => 'routing_vertices_pgr',
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][pgrouting][wayTableVertices]',
            ])
            ->add('pgWeighting', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.weighting.title',
                'required' => false,
                'empty_data' => 'length',
                'attr' => [
                    'advanced' => 'true',
                ],
               'property_path' => '[backendConfig][pgrouting][weighting]',
            ])
            ->add('pgSpeed',NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.pg.speed',
                'required' => false,
                'empty_data' => '5',
                'scale' => 1,
                //'rounding_mode' => IntegerToLocalizedStringTransformer::ROUND_UP,
                //'rounding_mode' => IntegerToLocalizedStringTransformer::class,
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[backendConfig][pgrouting][speed]',
            ])
            ->add('dateTimeFormat',TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.dateTimeFormat',
                'required' => false,
                'empty_data' => 'ms',
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[dateTimeFormat]',
            ])
            ->add('pgInstructions', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.instructions',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[backendConfig][pgrouting][instructions]',
            ])
        ;

        # GraphHopper
        $builder
            ->add('ghUrl', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.url',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][graphhopper][url]',
            ])
            ->add('ghWeighting', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.weighting.title',
                'required' => false,
                'empty_data' => 'fastest',
                'choices' => [
                    'mb.routing.backend.dialog.label.gh.weighting.fastest' => 'fastest',
                ],
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[backendConfig][graphhopper][weighting]',
            ])
            ->add('ghTransportationMode', ChoiceType::class, [
                'label' => 'mb.routing.frontend.dialog.label.transportationmode',
                'required' => false,
                'multiple' => true,
                'empty_data' => ['car'],
                'choices' => [
                    'mb.routing.frontend.dialog.label.car' => 'car',
                    'mb.routing.frontend.dialog.label.bike' => 'bike',
                    'mb.routing.frontend.dialog.label.foot' => 'foot',
                ],
                'attr' => [
                    'advanced' => 'false',
                    'driver' => 'graphhopper'
                ],
                'property_path' => '[backendConfig][graphhopper][transportationMode]',
            ])
            ->add('ghKey', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.key',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'advanced' => 'false',
                    'driver' => 'graphhopper',
                ],
                'property_path' => '[backendConfig][graphhopper][key]',
            ])
            ->add('ghOptimize',ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.optimize.label',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'attr' => [
                    'advanced' => 'false',
                    'driver' => 'graphhopper',
                ],
                'property_path' => '[backendConfig][graphhopper][optimize]',
            ])
            ->add('ghElevation', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.elevation',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'attr' => [
                    'advanced' => 'false',
                    'driver' => 'graphhopper',
                ],
                'property_path' => '[backendConfig][graphhopper][elevation]',
            ])
            ->add('ghInstructions', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.instructions',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][graphhopper][instructions]',
            ])
        ;

        # OSRM
        $builder
            ->add('osrmUrl', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.url',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][osrm][url]',
            ])
            ->add('osrmService', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.service',
                'required' => false,
                'empty_data' => 'route',
                "choices" => [
                    'mb.routing.backend.dialog.input.osrmservice.route' => 'route',
                    'mb.routing.backend.dialog.input.osrmservice.nearest' => 'nearest',
                    'mb.routing.backend.dialog.input.osrmservice.table' => 'table',
                    'mb.routing.backend.dialog.input.osrmservice.match' => 'match',
                    'mb.routing.backend.dialog.input.osrmservice.trip' => 'trip',
                    'mb.routing.backend.dialog.input.osrmservice.tile' => 'tile',
                ],
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][osrm][service]',
            ])
            ->add('osrmVersion', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.version',
                'required' => false,
                'empty_data' => 'v1',
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][osrm][version]',
            ])
            ->add('osrmTransportationMode', ChoiceType::class, [
                'label' => 'mb.routing.frontend.dialog.label.transportationmode',
                'required' => false,
                'multiple' => true,
                'empty_data' => ['car'],
                'choices' => [
                    'mb.routing.frontend.dialog.label.car' => 'car',
                    'mb.routing.frontend.dialog.label.bike' => 'bike',
                    'mb.routing.frontend.dialog.label.foot' => 'foot',
                ],
                'attr' => [
                    'advanced' => 'false',
                ],
                'property_path' => '[backendConfig][osrm][transportationMode]',
            ])
            ->add('osrmAlternatives', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.alternatives',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                    'mb.routing.backend.dialog.input.number' => 'number',
                ],
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[backendConfig][osrm][alternatives]',
            ])
            ->add('osrmSteps', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.instructions',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[backendConfig][osrm][steps]',
            ])
            ->add('osrmAnnotations', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.annotations',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                    'mb.routing.backend.dialog.input.nodes' => 'nodes',
                    'mb.routing.backend.dialog.input.distance' => 'distance',
                    'mb.routing.backend.dialog.input.duration' => 'duration',
                    'mb.routing.backend.dialog.input.datasources' => 'datasources',
                    'mb.routing.backend.dialog.input.weight' => 'weight',
                    'mb.routing.backend.dialog.input.speed' => 'speed',
                ],
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[backendConfig][osrm][annotations]',
            ])
            ->add('osrmOverview', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.overview',
                'required' => false,
                'empty_data' => 'full',
                'choices' => [
                    'mb.routing.backend.dialog.input.full' => 'full',
                    'mb.routing.backend.dialog.input.simplified' => 'simplified',
                ],
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[backendConfig][osrm][overview]',
            ])
            ->add('osrmContinueStraight', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.continueStraight',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.default' => 'default',
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'attr' => [
                    'advanced' => 'true',
                ],
                'property_path' => '[backendConfig][osrm][continueStraight]',
            ])
        ;
    }
}
