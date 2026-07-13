<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Mapbender\CoreBundle\Element\DataTransformer\SearchRouterRouteTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\ManagerBundle\Form\DataTransformer\YAMLDataTransformer;


class SearchRouterRouteAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new SearchRouterRouteTransformer());
        $builder->add('title', TextType::class, [
            'label' => 'mb.core.searchrouterroute.admin.title',
        ]);
        $yamlConfigType = $builder->create('configuration', TextareaType::class, [
            'label' => 'mb.core.searchrouterroute.admin.configuration',
        ]);
        $yamlConfigType->addViewTransformer(new YAMLDataTransformer(20));
        $builder->add($yamlConfigType);
    }
}
