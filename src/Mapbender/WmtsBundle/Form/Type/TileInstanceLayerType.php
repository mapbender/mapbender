<?php


namespace Mapbender\WmtsBundle\Form\Type;


use Mapbender\ManagerBundle\Form\Type\SourceInstanceItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TileInstanceLayerType extends AbstractType
{
    public function getParent()
    {
        return SourceInstanceItemType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('supportedCrs', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
                'label' => 'mb.wmts.wmtsloader.repo.tilematrixset.label.supportedcrs',
            ))
        ;
    }
}
