<?php

namespace Mapbender\RoutingBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mapbender\RoutingBundle\Element\Type\SearchRoutingElementAdminType;

/**
 * Class RoutingElementAdminType
 * @package Mapbender\RoutingBundle\Element\Type
 * @author Christian Kuntzsch
 * @author Robert Klemm
 */
class RoutingElementAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'routing';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('target', 'target_element',
                array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application'   => $options['application'],
                    'property_path' => '[target]',
                    'required'      => true,
                    )
            )
            ->add('type', 'choice', array('required' => true, 'choices' => array('dialog' => 'Dialog', 'element' => 'Element')))
            ->add('autoSubmit', 'checkbox', array('required' => false, 'property_path' => '[autoSubmit]'))
            ->add('addIntermediatePoints', 'checkbox', array('required' => false, 'property_path' => '[addIntermediatePoints]'))
            ->add('advanced', 'checkbox', array('required' => false))
            ->add('disableContextMenu', 'checkbox', array('required' => false, 'property_path' => '[disableContextMenu]'))
            ->add('addSearch', 'checkbox', array('required' => false, 'property_path' => '[addSearch]'))
            ->add('addReverseGeocoding', 'checkbox', array('required' => false, 'property_path' => '[addReverseGeocoding]'))
            ->add('buffer', 'number',
                array(
                    'label' => "mb.routing.backend.dialog.label.buffer",
                    'empty_data'  => 0,
                    'invalid_message' => "mb.routing.backend.dialog.error.buffer",
                    'error_bubbling'  => true,
                    'attr' => array(
                        'type' => 'number',
                        'min' => 0),
                    'property_path' => '[buffer]'
                ))
            ->add('color', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.color",
                    'required' => false,
                    'attr' => array('type' => 'color'),
                    'empty_data' => '#4286F4',
                    'property_path' => '[color]'
                )
            )
            ->add('width', 'number',
                array(
                    'label' => "mb.routing.backend.dialog.label.width",
                    'required' => false,
                    'empty_data' => 3,
                    'attr' => array(
                        'type' => 'number',
                        'min' => 0),
                    'property_path' => '[width]'
                ))
            ->add('opacity', 'range',
                array(
                    'label' => "mb.routing.backend.dialog.label.opacity",
                    'required' => false,
                    'empty_data' => 1,
                    'attr' => array(
                        'type' => 'number',
                        'step' => 0.1,
                        'min' => 0,
                        'max' => 1),
                    'property_path' => '[opacity]'
                ))
            ->add('startImagePath', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.startImagePath",
                    'empty_data' => null,
                    'required' => false,
                    'property_path' => '[styleMap][start][startImagePath]'
                ))
            ->add('startImageSize', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.startImageSize",
                    'required' => false,
                    'property_path' => '[styleMap][start][startImageSize]'
                ))
            ->add('startImageOffset', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.startImageOffset",
                    'required' => false,
                    'property_path' => '[styleMap][start][startImageOffset]'
                ))
            ->add('intermediateImagePath', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.intermediateImagePath",
                    'empty_data' => null,
                    'required' => false,
                    'property_path' => '[styleMap][intermediate][intermediateImagePath]'
                ))
            ->add('intermediateImageSize', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.intermediateImageSize",
                    'required' => false,
                    'property_path' => '[styleMap][intermediate][intermediateImageSize]'
                ))
            ->add('intermediateImageOffset', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.intermediateImageOffset",
                    'required' => false,
                    'property_path' => '[styleMap][intermediate][intermediateImageOffset]'
                ))
            ->add('destinationImagePath', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.destinationImagePath",
                    'empty_data' => null,
                    'required' => false,
                    'property_path' => '[styleMap][destination][destinationImagePath]'
                ))
            ->add('destinationImageSize', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.destinationImageSize",
                    'required' => false,
                    'property_path' => '[styleMap][destination][destinationImageSize]'
                ))
            ->add('destinationImageOffset', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.destinationImageOffset",
                    'required' => false,
                    'property_path' => '[styleMap][destination][destinationImageOffset]'
                ))
            ->add('infoText', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.infoText",
                    'required' => false,
                    'empty_data' => "{start} → {destination} </br> {length} will take {time}"
                ))
            ->add(
                'routingDriver', 'choice', array(
                    'label' => "mb.routing.backend.dialog.label.routingDriver",
                    'placeholder' => 'mb.routing.backend.dialog.label.chooseOption',
                    'empty_data' => 'graphhopper',
                    'required' => true,
                    'choices' => array(
                        'graphhopper'   => "mb.routing.backend.dialog.label.gh.titel",
                        'osrm'          => "mb.routing.backend.dialog.label.osrm.titel",
                        'pgrouting'     => "mb.routing.backend.dialog.label.pg.titel",
                        'trias'         => 'Trias')
                ))
            ->add(
                $builder->create('search', new SearchRoutingElementAdminType())
            )->add(
                $builder->create('reverse',new ReverseRoutingElementAdminType())
            )
        ;

        # PgRouting Config
        $builder
            ->add('connection', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.pg.connection",
                    'required' => false,
                    'empty_data' => null,
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][pgrouting][connection]'
                )
            )
            ->add('pgWayTable', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.pg.pgWayTable",
                    'required' => false,
                    'empty_data' => 'routing',
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][pgrouting][wayTable]'
                )
           )
            ->add('pgWayTableVertices', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.pg.pgWayTableVertices",
                    'required' => false,
                    'empty_data' => 'routing_vertices_pgr',
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][pgrouting][wayTableVertices]'
                )
            )
            ->add('pgWeighting', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.gh.weighting.title",
                    'required' => false,
                    'empty_data' => 'length',
                    'attr' => array(
                        'advanced' => 'true',
                        ),
                    'property_path' => '[backendConfig][pgrouting][weighting]'
                )
            )
            ->add('pgSpeed','number',
                array(
                    'label' => "mb.routing.backend.dialog.label.pg.speed",
                    'required' => false,
                    'empty_data' => '5',
                    'scale' => 1,
                    'rounding_mode' => IntegerToLocalizedStringTransformer::ROUND_UP,
                    'attr' => array(
                        'advanced' => 'true',
                        ),
                    'property_path' => '[backendConfig][pgrouting][speed]'
                )
            )
            ->add('dateTimeFormat','text',
                array(
                    'label' => "mb.routing.backend.dialog.label.dateTimeFormat",
                    'required' => false,
                    'empty_data' => 'ms',
                    'attr' => array(
                        'advanced' => 'true',
                        ),
                    'property_path' => '[dateTimeFormat]'
                )
            )
            ->add('pgInstructions', 'choice', array(
                'label' => "mb.routing.backend.dialog.label.instructions",
                'required' => false,
                'empty_data' => 'false',
                'choices' => array(
                    'false'     => "mb.routing.backend.dialog.input.no",
                    'true'      => "mb.routing.backend.dialog.input.yes"),
                'attr' => array(
                    'advanced' => 'true',
                    ),
                'property_path' => '[backendConfig][pgrouting][instructions]'
            ))
        ;

        # GraphHopperDriver Config
        $builder
            ->add('ghUrl', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.url",
                    'required' => false,
                    'empty_data' => null,
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][graphhopper][url]'
                )
            )
            ->add('ghWeighting', 'choice',
                array(
                    'label' => "mb.routing.backend.dialog.label.gh.weighting.title",
                    'required' => false,
                    'empty_data' => 'fastest',
                    "choices" => array(
                        'fastest'       => "mb.routing.backend.dialog.label.gh.weighting.fastest"
                    ),
                    'attr' => array(
                        'advanced' => 'true',
                        ),
                    'property_path' => '[backendConfig][graphhopper][weighting]'
                )
            )
            ->add('ghTransportationMode', 'choice',
                array(
                    'label' => "mb.routing.frontend.dialog.label.transportationmode",
                    'required' => false,
                    'multiple' => true,
                    'empty_data' => array("car"),
                    "choices" => array(
                        'car'       => "mb.routing.frontend.dialog.label.car",
                        'bike'      => "mb.routing.frontend.dialog.label.bike",
                        'foot'      => "mb.routing.frontend.dialog.label.foot"),
                    'attr' => array(
                        'advanced' => 'false',
                        'driver' => 'graphhopper'),
                    'property_path' => '[backendConfig][graphhopper][transportationMode]'
                ))
            ->add('ghKey', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.gh.key",
                    'required' => false,
                    'empty_data' => null,
                    'attr' => array(
                        'advanced' => 'false',
                        'driver' => 'graphhopper'),
                    'property_path' => '[backendConfig][graphhopper][key]'
                ))
            ->add('ghOptimize','choice',array(
                'label' => "mb.routing.backend.dialog.label.gh.optimize.label",
                'required' => false,
                'empty_data' => 'false',
                'choices' => array(
                    'false'     => "mb.routing.backend.dialog.input.no",
                    'true'      => "mb.routing.backend.dialog.input.yes"),
                'attr' => array(
                    'advanced' => 'false',
                    'driver' => 'graphhopper'),
                'property_path' => '[backendConfig][graphhopper][optimize]'
            ))
            ->add('ghElevation', 'choice', array(
                'label' => "mb.routing.backend.dialog.label.gh.elevation",
                'required' => false,
                'empty_data' => 'false',
                'choices' => array(
                    'false'     => "mb.routing.backend.dialog.input.no",
                    'true'      => "mb.routing.backend.dialog.input.yes"),
                'attr' => array(
                    'advanced' => 'false',
                    'driver' => 'graphhopper'),
                'property_path' => '[backendConfig][graphhopper][elevation]'
            ))
            ->add('ghInstructions', 'choice', array(
                'label' => "mb.routing.backend.dialog.label.instructions",
                'required' => false,
                'empty_data' => 'false',
                'choices' => array(
                    'false'     => "mb.routing.backend.dialog.input.no",
                    'true'      => "mb.routing.backend.dialog.input.yes"),
                'attr' => array(
                    'advanced' => 'false',
                    ),
                'property_path' => '[backendConfig][graphhopper][instructions]'
            ))
        ;

        # OSRM Config
        # Request-Params
        $builder
            ->add('osrmUrl', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.url",
                    'required' => false,
                    'empty_data' => null,
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][osrm][url]'
                )
            )
            ->add('osrmService', 'choice',
                array(
                    'label' => "mb.routing.backend.dialog.label.osrm.service",
                    'required' => false,
                    'empty_data' => 'route',
                    "choices" => array(
                        'route'       => "mb.routing.backend.dialog.input.osrmservice.route",
//                        'nearest'       => "mb.routing.backend.dialog.input.osrmservice.nearest",
//                        'table'       => "mb.routing.backend.dialog.input.osrmservice.table",
//                        'match'       => "mb.routing.backend.dialog.input.osrmservice.match",
//                        'trip'       => "mb.routing.backend.dialog.input.osrmservice.trip",
//                        'tile'       => "mb.routing.backend.dialog.input.osrmservice.tile",
                    ),
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][osrm][service]'
                )
            )
            ->add('osrmVersion', 'text',
                array(
                    'label' => "mb.routing.backend.dialog.label.osrm.version",
                    'required' => false,
                    'empty_data' => 'v1',
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][osrm][version]'
                )
            )
            ->add('osrmTransportationMode', 'choice',
                array(
                    'label' => "mb.routing.frontend.dialog.label.transportationmode",
                    'required' => false,
                    'multiple' => true,
                    'empty_data' => null,
                    "choices" => array(
                        'car'       => "mb.routing.frontend.dialog.label.car",
                        'bike'      => "mb.routing.frontend.dialog.label.bike",
                        'foot'      => "mb.routing.frontend.dialog.label.foot"),
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][osrm][transportationMode]'
                )
            )

            # Request-Option
            ## Route service
            ->add('osrmAlternatives', 'choice', array(
                'label' => "mb.routing.backend.dialog.label.osrm.alternatives",
                'required' => false,
                'empty_data' => 'false',
                'choices' => array(
                    'false'     => "mb.routing.backend.dialog.input.no",
                    'true'      => "mb.routing.backend.dialog.input.yes",
                    'number'    => "mb.routing.backend.dialog.input.number"
                ),
                'attr' => array(
                    'advanced' => 'true',
                    ),
                'property_path' => '[backendConfig][osrm][alternatives]'
            ))
            ->add('osrmSteps', 'choice', array(
                'label' => "mb.routing.backend.dialog.label.instructions",
                'required' => false,
                'empty_data' => 'false',
                'choices' => array(
                    'false'     => "mb.routing.backend.dialog.input.no",
                    'true'      => "mb.routing.backend.dialog.input.yes"),
                'attr' => array(
                    'advanced' => 'true',
                    ),
                'property_path' => '[backendConfig][osrm][steps]'
            ))
            ->add('osrmAnnotations', 'choice', array(
                'label' => "mb.routing.backend.dialog.label.osrm.annotations",
                'required' => false,
                'empty_data' => 'false',
                'choices' => array(
                    'false'         => "mb.routing.backend.dialog.input.no",
                    'true'          => "mb.routing.backend.dialog.input.yes",
                    'nodes'         => "mb.routing.backend.dialog.input.nodes",
                    'distance'      => "mb.routing.backend.dialog.input.distance",
                    'duration'      => "mb.routing.backend.dialog.input.duration",
                    'datasources'   => "mb.routing.backend.dialog.input.datasources",
                    'weight'        => "mb.routing.backend.dialog.input.weight",
                    'speed'         => "mb.routing.backend.dialog.input.speed",
                ),
                'attr' => array(
                    'advanced' => 'true',
                    ),
                'property_path' => '[backendConfig][osrm][annotations]'
            ))
            ->add('osrmOverview', 'choice', array(
                'label' => "mb.routing.backend.dialog.label.osrm.overview",
                'required' => false,
                'empty_data' => 'full',
                'choices' => array(
                    'full'          => "mb.routing.backend.dialog.input.full",
                    'simplified'    => "mb.routing.backend.dialog.input.simplified"
                ),
                'attr' => array(
                    'advanced' => 'true',
                    ),
                'property_path' => '[backendConfig][osrm][overview]'
            ))
            ->add('osrmContinueStraight', 'choice', array(
                'label' => "mb.routing.backend.dialog.label.osrm.continueStraight",
                'required' => false,
                'empty_data' => 'false',
                'choices' => array(
                    'default'   => "mb.routing.backend.dialog.input.default",
                    'false'     => "mb.routing.backend.dialog.input.no",
                    'true'      => "mb.routing.backend.dialog.input.yes"),
                'attr' => array(
                    'advanced' => 'true',
                    ),
                'property_path' => '[backendConfig][osrm][continueStraight]'
            ))
        ;


    }
}
