<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class SearchRouterAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('width', 'Symfony\Component\Form\Extension\Core\Type\IntegerType')
            ->add('height', 'Symfony\Component\Form\Extension\Core\Type\IntegerType')
            ->add('routes', SortableCollectionType::class, array(
                'entry_type' => 'Mapbender\CoreBundle\Element\Type\SearchRouterRouteAdminType',
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
            ))
        ;
    }

}
