<?php

namespace Mapbender\RoutingBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('driver', ChoiceType::class, [
                'label' => 'mb.routing.backend.dialog.label.reverse.geocodeDriver',
                'required' => false,
                'empty_data' => 'sql',
                'choices' => [
                    # 'Nominatim' => 'nominatim',
                    'PostgreSQL' => 'sql',
                    # 'Solr' => 'solr',
                ],
            ])
            ->add('connection', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.reverse.connection',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('table', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.reverse.revTableName',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('rowGeoWay', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.reverse.revRowGeoWay',
                'required' => false,
                'empty_data' => 'the_geom',
            ])
            ->add('rowSearch', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.reverse.revRowSearch',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('searchBuffer', TextType::class, [
                'label' => 'mb.routing.backend.dialog.label.reverse.revRowSearchBuffer',
                'required' => false,
                'empty_data' => 50,
                'attr' => [
                    'type' => 'number',
                    'min' => 0,
                ],
            ])
        ;
    }
}
