<?php

namespace Mapbender\RoutingBundle\Element\Type;

use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class RoutingElementAdminType
 * @package Mapbender\RoutingBundle\Element\Type
 */
class RoutingAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(protected TranslatorInterface $translator) {

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('advancedSettings', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.routing.backend.dialog.label.advanced',
            ])
            ->add('autoSubmit', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.routing.backend.dialog.label.autoSubmit',
            ])
            ->add('allowIntermediatePoints', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.routing.backend.dialog.label.addIntermediatePoints',
            ])
            ->add('useSearch', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.routing.backend.dialog.label.search',
            ])
            // uncomment once reverse geocoding is implemented:
            /*
            ->add('useReverseGeocoding', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.routing.backend.dialog.label.reverseGeocoding',
            ])
            */
            ->add('buffer', NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.buffer',
                'empty_data' => 0,
                'invalid_message' => 'mb.routing.backend.dialog.error.buffer',
                'error_bubbling' => true,
                'attr' => [
                    'type' => 'number',
                    'min' => 0,
                ],
            ])
            ->add('infoText', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.infoText',
                'required' => false,
                'empty_data' => "{start} → {destination} </br> {length} will take {time}",
            ])
            ->add('routingDriver', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.routingDriver',
                'placeholder' => 'mb.routing.backend.dialog.label.chooseOption',
                'required' => true,
                'choices' => [
                    'mb.routing.backend.dialog.label.osrm.titel' => 'osrm',
                    #'mb.routing.backend.dialog.label.gh.titel' => 'graphhopper',
                    #'mb.routing.backend.dialog.label.pg.titel' => 'pgrouting',
                    #'Trias' => 'trias',
                ],
            ])
            ->add('lineColor', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.color',
                'required' => false,
                'empty_data' => 'rgba(66, 134, 244, 1)',
                'property_path' => '[routingStyles][lineColor]',
                'attr' => [
                    'class' => '-js-init-colorpicker',
                ],
            ])
            ->add('lineWidth', NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.width',
                'required' => false,
                'empty_data' => 3,
                'attr' => [
                    'type' => 'number',
                    'min' => 0,
                ],
                'property_path' => '[routingStyles][lineWidth]',
            ])
            ->add('startImagePath', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.startImagePath',
                'required' => false,
                'property_path' => '[routingStyles][startIcon][imagePath]',
            ])
            ->add('startImageSize', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.startImageSize',
                'required' => false,
                'property_path' => '[routingStyles][startIcon][imageSize]'
            ])
            ->add('startImageOffset', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.startImageOffset',
                'required' => false,
                'property_path' => '[routingStyles][startIcon][imageOffset]',
            ])
            ->add('intermediateImagePath', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.intermediateImagePath',
                'empty_data' => null,
                'required' => false,
                'property_path' => '[routingStyles][intermediateIcon][imagePath]',
            ])
            ->add('intermediateImageSize', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.intermediateImageSize',
                'required' => false,
                'property_path' => '[routingStyles][intermediateIcon][imageSize]',
            ])
            ->add('intermediateImageOffset', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.intermediateImageOffset',
                'required' => false,
                'property_path' => '[routingStyles][intermediateIcon][imageOffset]',
            ])
            ->add('destinationImagePath', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.destinationImagePath',
                'required' => false,
                'property_path' => '[routingStyles][destinationIcon][imagePath]',
            ])
            ->add('destinationImageSize', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.destinationImageSize',
                'required' => false,
                'property_path' => '[routingStyles][destinationIcon][imageSize]',
            ])
            ->add('destinationImageOffset', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.destinationImageOffset',
                'required' => false,
                'property_path' => '[routingStyles][destinationIcon][imageOffset]',
            ])
            ->add(
                $builder->create('searchConfig', SearchAdminType::class)
            )
            ->add(
                $builder->create('reverseGeocodingConfig',ReverseGeocodingAdminType::class)
            )
        ;

        # PgRouting
        $builder
            ->add('connection', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.pg.connection',
                'required' => false,
                'empty_data' => null,
                'property_path' => '[routingConfig][pgrouting][connection]',
            ])
            ->add('pgWayTable', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.pg.pgWayTable',
                'required' => false,
                'empty_data' => 'routing',
                'property_path' => '[routingConfig][pgrouting][wayTable]',
            ])
            ->add('pgWayTableVertices', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.pg.pgWayTableVertices',
                'required' => false,
                'empty_data' => 'routing_vertices_pgr',
                'property_path' => '[routingConfig][pgrouting][wayTableVertices]',
            ])
            ->add('pgWeighting', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.weighting.title',
                'required' => false,
                'empty_data' => 'length',
                'property_path' => '[routingConfig][pgrouting][weighting]',
            ])
            ->add('pgSpeed',NumberType::class, [
                'label' => 'mb.routing.backend.dialog.label.pg.speed',
                'required' => false,
                'empty_data' => '5',
                'scale' => 1,
                //'rounding_mode' => IntegerToLocalizedStringTransformer::ROUND_UP,
                //'rounding_mode' => IntegerToLocalizedStringTransformer::class,
                'property_path' => '[routingConfig][pgrouting][speed]',
            ])
            ->add('pgInstructions', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.instructions',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'property_path' => '[routingConfig][pgrouting][instructions]',
            ])
        ;

        # GraphHopper
        $builder
            ->add('ghUrl', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.url',
                'required' => false,
                'empty_data' => null,
                'property_path' => '[routingConfig][graphhopper][url]',
            ])
            ->add('ghWeighting', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.weighting.title',
                'required' => false,
                'empty_data' => 'fastest',
                'choices' => [
                    'mb.routing.backend.dialog.label.gh.weighting.fastest' => 'fastest',
                ],
                'property_path' => '[routingConfig][graphhopper][weighting]',
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
                'property_path' => '[routingConfig][graphhopper][transportationMode]',
            ])
            ->add('ghKey', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.key',
                'required' => false,
                'empty_data' => null,
                'property_path' => '[routingConfig][graphhopper][key]',
            ])
            ->add('ghOptimize',ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.optimize.label',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'property_path' => '[routingConfig][graphhopper][optimize]',
            ])
            ->add('ghElevation', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.gh.elevation',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'property_path' => '[routingConfig][graphhopper][elevation]',
            ])
            ->add('ghInstructions', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.instructions',
                'required' => false,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'property_path' => '[routingConfig][graphhopper][instructions]',
            ])
        ;

        # OSRM
        $builder
            ->add('osrmUrl', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.routing.backend.dialog.label.url',
                'required' => false,
                'empty_data' => null,
                'property_path' => '[routingConfig][osrm][url]',
                'help' => 'mb.routing.backend.dialog.label.url_help',
            ], $this->translator))
            ->add('osrmService', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.service',
                'required' => true,
                'empty_data' => 'route',
                'choices' => [
                    'mb.routing.backend.dialog.input.osrmservice.route' => 'route',
                ],
                'property_path' => '[routingConfig][osrm][service]',
            ])
            ->add('osrmVersion', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.version',
                'required' => true,
                'empty_data' => 'v1',
                // add more osrm versions here, if available
                'choices' => [
                    'v1' => 'v1',
                ],
                'property_path' => '[routingConfig][osrm][version]',
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
                'property_path' => '[routingConfig][osrm][transportationMode]',
            ])
            // uncomment, if alternative routes should be implemented
            /*
            ->add('osrmAlternatives', IntegerType::class, [
                'label' => 'mb.routing.backend.dialog.label.osrm.alternatives',
                'required' => false,
                'empty_data' => 0,
                'attr' => [
                    'min' => 0,
                    'max' => 3,
                ],
                'property_path' => '[routingConfig][osrm][alternatives]',
            ])
            */
            ->add('osrmSteps', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.instructions',
                'required' => true,
                'empty_data' => 'false',
                'choices' => [
                    'mb.routing.backend.dialog.input.no' => 'false',
                    'mb.routing.backend.dialog.input.yes' => 'true',
                ],
                'property_path' => '[routingConfig][osrm][steps]',
            ])
        ;
    }
}
