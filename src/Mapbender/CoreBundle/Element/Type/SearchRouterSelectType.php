<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\SearchRouter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchRouterSelectType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(SearchRouter::getDefaultConfiguration());
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $routes = array();
        foreach ($options['routes'] as $name => $conf) {
            $routes[$conf['title']] = $name;
        }

        $builder->add('route', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
            'choices'  => $routes,
            'choices_as_values' => true,
            'mapped'   => false,
            'multiple' => false,
            'expanded' => false,
            'attr'     => array(
                'autocomplete' => 'off'
            )));
    }
}
