<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\CoreBundle\Element\Type\SearchRouterRouteAdminType;


class SearchRouterAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'search_form';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'routes' => array()));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('routes', 'collection', array(
            'type' => new SearchRouterRouteAdminType(),
            'allow_add' => true,
            'allow_delete' => true
        ));
    }
}
