<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\FrameworkBundle\Component\IconIndex;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IconClassType extends AbstractType implements EventSubscriberInterface
{
    /** @var IconIndex */
    protected $iconIndex;

    public function __construct(IconIndex $iconIndex)
    {
        $this->iconIndex = $iconIndex;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['dropdown_elements_html'] = true;
    }


    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $choicesWithoutIcon = $this->iconIndex->getChoices();
        ksort($choicesWithoutIcon);
        $choices = [];
        foreach ($choicesWithoutIcon as $label => $code) {
            $choices[$this->iconIndex->getIconMarkup($code) . '&nbsp;&nbsp;' . $label] = $code;
        }

        $resolver->setDefaults(array(
            'placeholder' => function(Options $options) {
                if ($options['required']) {
                    return 'mb.form.choice_required';
                } else {
                    return 'mb.form.choice_optional';
                }
            },
            'choices' => $choices,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $evt)
    {
        $value = $evt->getData();
        if ($value) {
            $evt->setData($this->iconIndex->normalizeAlias($value));
        }
    }
}
