<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\ManagerBundle\Form\DataTransformer\YAMLDataTransformer;


class SearchRouterRouteAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'search_form_route';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array());
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $yamlTransformer = new YAMLDataTransformer(20);
        $builder->add('title', 'text', array(
            'label' => 'Title'));
        $builder->add($builder->create('configuration', 'textarea', array(
            'label' => 'Configuration','attr' => array('class' => 'code-yaml')))->addViewTransformer($yamlTransformer));
    }

}
