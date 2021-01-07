<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SourceInstanceLayerCollectionType extends AbstractType
{

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\CollectionType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // Supply prototype view, so the "summary" fields can access labels from
            // the collection entry forms
            'prototype' => true,
            // NOTE: we do NOT want an extensible collection, but Symfony will only build
            // the prototype view if 'allow_add' is true
            // see https://github.com/symfony/symfony/blob/2.8/src/Symfony/Component/Form/Extension/Core/Type/CollectionType.php#L29
            'allow_add' => true,
        ));
    }
}
