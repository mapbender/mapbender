<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the type of sidepane in a fullscreen application.
 * NOTE: the entry for this in persisted RegionProperties is called "name".
 */
class SidepaneTypeType extends AbstractType
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function getBlockPrefix()
    {
        return 'sidepane_type';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $choiceLabels = array(
            // not very localized, but same as previous Symfony default behaviour
            '' => 'None',
            'tabs' => 'mb.manager.template.region.tabs.label',
            'accordion' => 'mb.manager.template.region.accordion.label',
        );
        $resolver->setDefaults(array(
            'expanded' => true,
            'choices' => array_keys($choiceLabels),
            'label' => false,
            'choice_label' => false,
            'choice_attr' => function($choice) use ($choiceLabels) {
                return array(
                    'title' => $choiceLabels[$choice],
                    'class' => 'hidden',
                );
            },
        ));
        if (Kernel::MAJOR_VERSION < 3) {
            $resolver->setDefaults(array(
                'choices_as_values' => true,
            ));
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['iconMap'] = array(
            '' => 'fa fa-square-o', // @todo Fontawesome 5: far fa-square; or find some other icon
            'tabs' => 'fa fas fa-folder',
            'accordion' => 'fa fas fa-bars',
        );
    }
}
