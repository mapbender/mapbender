<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\DataTransformer\SearchRouterRouteTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\ManagerBundle\Form\DataTransformer\YAMLDataTransformer;


class SearchRouterRouteAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new SearchRouterRouteTransformer());
        $builder->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
            'label' => 'Title',
        ));
        $yamlConfigType = $builder->create('configuration', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
            'label' => 'Configuration',
        ));
        $yamlConfigType->addViewTransformer(new YAMLDataTransformer(20));
        $builder->add($yamlConfigType);
    }
}
