<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 *
 */
class SearchRouterSelectType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'search_routes';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'routes' => array()));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $routes = array();
        foreach ($options['routes'] as $name => $conf) {
            $routes[$name] = $conf['title'];
        }

        $builder->add('route', 'choice', array(
            'choices' => $routes,
            'mapped' => false,
            'property_path' => false,
            'multiple' => false,
            'expanded' => false,
            'attr' => array(
                'autocomplete' => 'off')));
    }

}
