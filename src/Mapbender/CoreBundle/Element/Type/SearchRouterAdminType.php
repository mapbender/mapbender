<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class SearchRouterAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('width', IntegerType::class, [
                'label' => 'mb.core.searchrouter.admin.width',
            ])
            ->add('height', IntegerType::class, [
                'label' => 'mb.core.searchrouter.admin.height',
            ])
            ->add('routes', SortableCollectionType::class, [
                'entry_type' => SearchRouterRouteAdminType::class,
                'label' => 'mb.core.searchrouter.admin.routes',
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
            ])
        ;
    }

}
