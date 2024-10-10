<?php

namespace Mapbender\RoutingBundle\Element\Type;

use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mapbender\RoutingBundle\Element\Type\SearchRoutingElementAdminType;
use Symfony\Component\Validator\Constraints\Choice;

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
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\ControlTargetType',
                array(
                    //'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    //'application'   => $options['application'],
                    'property_path' => '[target]',
                    'required'      => true,
                    )
            )
            ->add('type', ChoiceType::class, array('required' => true, 'choices' => array('dialog' => 'Dialog', 'element' => 'Element')))
            ->add('autoSubmit', CheckboxType::class, array('required' => false, 'property_path' => '[autoSubmit]'))
            ->add('addIntermediatePoints', CheckboxType::class, array('required' => false, 'property_path' => '[addIntermediatePoints]'))
            ->add('advanced', CheckboxType::class, array('required' => false))
            ->add('disableContextMenu', CheckboxType::class, array('required' => false, 'property_path' => '[disableContextMenu]'))
            ->add('addSearch', CheckboxType::class, array('required' => false, 'property_path' => '[addSearch]'))
            ->add('addReverseGeocoding', CheckboxType::class, array('required' => false, 'property_path' => '[addReverseGeocoding]'))
            ->add('buffer', NumberType::class,
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
            ->add('color', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.color",
                    'required' => false,
                    'attr' => array('type' => 'color'),
                    'empty_data' => '#4286F4',
                    'property_path' => '[color]'
                )
            )
            ->add('width', NumberType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.width",
                    'required' => false,
                    'empty_data' => 3,
                    'attr' => array(
                        'type' => 'number',
                        'min' => 0),
                    'property_path' => '[width]'
                ))
            ->add('opacity', RangeType::class,
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
            ->add('startImagePath', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.startImagePath",
                    'empty_data' => null,
                    'required' => false,
                    'property_path' => '[styleMap][start][startImagePath]'
                ))
            ->add('startImageSize', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.startImageSize",
                    'required' => false,
                    'property_path' => '[styleMap][start][startImageSize]'
                ))
            ->add('startImageOffset', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.startImageOffset",
                    'required' => false,
                    'property_path' => '[styleMap][start][startImageOffset]'
                ))
            ->add('intermediateImagePath', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.intermediateImagePath",
                    'empty_data' => null,
                    'required' => false,
                    'property_path' => '[styleMap][intermediate][intermediateImagePath]'
                ))
            ->add('intermediateImageSize', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.intermediateImageSize",
                    'required' => false,
                    'property_path' => '[styleMap][intermediate][intermediateImageSize]'
                ))
            ->add('intermediateImageOffset', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.intermediateImageOffset",
                    'required' => false,
                    'property_path' => '[styleMap][intermediate][intermediateImageOffset]'
                ))
            ->add('destinationImagePath', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.destinationImagePath",
                    'empty_data' => null,
                    'required' => false,
                    'property_path' => '[styleMap][destination][destinationImagePath]'
                ))
            ->add('destinationImageSize', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.destinationImageSize",
                    'required' => false,
                    'property_path' => '[styleMap][destination][destinationImageSize]'
                ))
            ->add('destinationImageOffset', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.destinationImageOffset",
                    'required' => false,
                    'property_path' => '[styleMap][destination][destinationImageOffset]'
                ))
            ->add('infoText', TextType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.infoText",
                    'required' => false,
                    'empty_data' => "{start} → {destination} </br> {length} will take {time}"
                ))
            ->add(
                'routingDriver', ChoiceType::class, array(
                    'label' => "mb.routing.backend.dialog.label.routingDriver",
                    'placeholder' => 'mb.routing.backend.dialog.label.chooseOption',
                    'empty_data' => 'graphhopper',
                    'required' => true,
                    'choices' => array(
//                        'graphhopper'   => "mb.routing.backend.dialog.label.gh.titel",
                        'osrm'          => "mb.routing.backend.dialog.label.osrm.titel"
//                        'pgrouting'     => "mb.routing.backend.dialog.label.pg.titel",
//                        'trias'         => 'Trias'
                    )
                ))
            ->add(
                $builder->create('search', SearchRoutingElementAdminType::class)
            )
           /* ->add(
                $builder->create('reverse',ReverseRoutingElementAdminType::class)
            )*/
        ;

        # PgRouting Config
        $builder
//            ->add('connection', TextType::class,
//                array(
//                    'label' => "mb.routing.backend.dialog.label.pg.connection",
//                    'required' => false,
//                    'empty_data' => null,
//                    'attr' => array(
//                        'advanced' => 'false',
//                        ),
//                    'property_path' => '[backendConfig][pgrouting][connection]'
//                )
//            )
//            ->add('pgWayTable', TextType::class,
//                array(
//                    'label' => "mb.routing.backend.dialog.label.pg.pgWayTable",
//                    'required' => false,
//                    'empty_data' => 'routing',
//                    'attr' => array(
//                        'advanced' => 'false',
//                        ),
//                    'property_path' => '[backendConfig][pgrouting][wayTable]'
//                )
//           )
//            ->add('pgWayTableVertices', TextType::class,
//                array(
//                    'label' => "mb.routing.backend.dialog.label.pg.pgWayTableVertices",
//                    'required' => false,
//                    'empty_data' => 'routing_vertices_pgr',
//                    'attr' => array(
//                        'advanced' => 'false',
//                        ),
//                    'property_path' => '[backendConfig][pgrouting][wayTableVertices]'
//                )
//            )
//            ->add('pgWeighting', TextType::class,
//                array(
//                    'label' => "mb.routing.backend.dialog.label.gh.weighting.title",
//                    'required' => false,
//                    'empty_data' => 'length',
//                    'attr' => array(
//                        'advanced' => 'true',
//                        ),
//                    'property_path' => '[backendConfig][pgrouting][weighting]'
//                )
//            )
//            ->add('pgSpeed',NumberType::class,
//                array(
//                    'label' => "mb.routing.backend.dialog.label.pg.speed",
//                    'required' => false,
//                    'empty_data' => '5',
//                    'scale' => 1,
//                    //'rounding_mode' => IntegerToLocalizedStringTransformer::ROUND_UP,
//                    //'rounding_mode' => IntegerToLocalizedStringTransformer::class,
//                    'attr' => array(
//                        'advanced' => 'true',
//                        ),
//                    'property_path' => '[backendConfig][pgrouting][speed]'
//                )
//            )
            ->add('dateTimeFormat',TextType::class,
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
//            ->add('pgInstructions', ChoiceType::class, array(
//                'label' => "mb.routing.backend.dialog.label.instructions",
//                'required' => false,
//                'empty_data' => 'false',
//                'choices' => array(
//                    'false'     => "mb.routing.backend.dialog.input.no",
//                    'true'      => "mb.routing.backend.dialog.input.yes"),
//                'attr' => array(
//                    'advanced' => 'true',
//                    ),
//                'property_path' => '[backendConfig][pgrouting][instructions]'
//            ))
        ;

        # GraphHopperDriver Config
//        $builder
//            ->add('ghUrl', TextType::class,
//                array(
//                    'label' => "mb.routing.backend.dialog.label.url",
//                    'required' => false,
//                    'empty_data' => null,
//                    'attr' => array(
//                        'advanced' => 'false',
//                        ),
//                    'property_path' => '[backendConfig][graphhopper][url]'
//                )
//            )
//            ->add('ghWeighting', ChoiceType::class,
//                array(
//                    'label' => "mb.routing.backend.dialog.label.gh.weighting.title",
//                    'required' => false,
//                    'empty_data' => 'fastest',
//                    "choices" => array(
//                        'fastest'       => "mb.routing.backend.dialog.label.gh.weighting.fastest"
//                    ),
//                    'attr' => array(
//                        'advanced' => 'true',
//                        ),
//                    'property_path' => '[backendConfig][graphhopper][weighting]'
//                )
//            )
//            ->add('ghTransportationMode', ChoiceType::class,
//                array(
//                    'label' => "mb.routing.frontend.dialog.label.transportationmode",
//                    'required' => false,
//                    'multiple' => true,
//                    'empty_data' => array("car"),
//                    "choices" => array(
//                        'car'       => "mb.routing.frontend.dialog.label.car",
//                        'bike'      => "mb.routing.frontend.dialog.label.bike",
//                        'foot'      => "mb.routing.frontend.dialog.label.foot"),
//                    'attr' => array(
//                        'advanced' => 'false',
//                        'driver' => 'graphhopper'),
//                    'property_path' => '[backendConfig][graphhopper][transportationMode]'
//                ))
//            ->add('ghKey', TextType::class,
//                array(
//                    'label' => "mb.routing.backend.dialog.label.gh.key",
//                    'required' => false,
//                    'empty_data' => null,
//                    'attr' => array(
//                        'advanced' => 'false',
//                        'driver' => 'graphhopper'),
//                    'property_path' => '[backendConfig][graphhopper][key]'
//                ))
//            ->add('ghOptimize',ChoiceType::class,array(
//                'label' => "mb.routing.backend.dialog.label.gh.optimize.label",
//                'required' => false,
//                'empty_data' => 'false',
//                'choices' => array(
//                    'false'     => "mb.routing.backend.dialog.input.no",
//                    'true'      => "mb.routing.backend.dialog.input.yes"),
//                'attr' => array(
//                    'advanced' => 'false',
//                    'driver' => 'graphhopper'),
//                'property_path' => '[backendConfig][graphhopper][optimize]'
//            ))
//            ->add('ghElevation', ChoiceType::class, array(
//                'label' => "mb.routing.backend.dialog.label.gh.elevation",
//                'required' => false,
//                'empty_data' => 'false',
//                'choices' => array(
//                    'false'     => "mb.routing.backend.dialog.input.no",
//                    'true'      => "mb.routing.backend.dialog.input.yes"),
//                'attr' => array(
//                    'advanced' => 'false',
//                    'driver' => 'graphhopper'),
//                'property_path' => '[backendConfig][graphhopper][elevation]'
//            ))
//            ->add('ghInstructions', ChoiceType::class, array(
//                'label' => "mb.routing.backend.dialog.label.instructions",
//                'required' => false,
//                'empty_data' => 'false',
//                'choices' => array(
//                    'false'     => "mb.routing.backend.dialog.input.no",
//                    'true'      => "mb.routing.backend.dialog.input.yes"),
//                'attr' => array(
//                    'advanced' => 'false',
//                    ),
//                'property_path' => '[backendConfig][graphhopper][instructions]'
//            ))
//        ;

        # OSRM Config
        # Request-Params
        $builder
            ->add('osrmUrl', TextType::class,
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
            ->add('osrmService', ChoiceType::class,
                array(
                    'label' => "mb.routing.backend.dialog.label.osrm.service",
                    'required' => false,
                    'empty_data' => 'route',
                    "choices" => array(
                        'route'       => "mb.routing.backend.dialog.input.osrmservice.route",
                        'nearest'       => "mb.routing.backend.dialog.input.osrmservice.nearest",
                        'table'       => "mb.routing.backend.dialog.input.osrmservice.table",
                        'match'       => "mb.routing.backend.dialog.input.osrmservice.match",
                        'trip'       => "mb.routing.backend.dialog.input.osrmservice.trip",
                        'tile'       => "mb.routing.backend.dialog.input.osrmservice.tile",
                    ),
                    'attr' => array(
                        'advanced' => 'false',
                        ),
                    'property_path' => '[backendConfig][osrm][service]'
                )
            )
            ->add('osrmVersion', TextType::class,
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
            ->add('osrmTransportationMode', ChoiceType::class,
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
            ->add('osrmAlternatives', ChoiceType::class, array(
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
            ->add('osrmSteps', ChoiceType::class, array(
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
            ->add('osrmAnnotations', ChoiceType::class, array(
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
            ->add('osrmOverview', ChoiceType::class, array(
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
            ->add('osrmContinueStraight', ChoiceType::class, array(
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
