<?php


namespace Mapbender\CoreBundle\Extension;


use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceListView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function Symfony\Bridge\Twig\Extension\twig_is_selected_choice;

/**
 * Adds get_selected_choice to extract the ChoiceView corresponding to a
 * specific value.
 *
 * Complements (test only) selectedchoice
 * @see \Symfony\Bridge\Twig\Extension\twig_is_selected_choice()
 */
class FormExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            'get_value_choice' => new TwigFunction('get_value_choice', array($this, 'get_value_choice')),
            'first_choice' => new TwigFunction('first_choice', array($this, 'first_choice')),
        );
    }

    /**
     * @param ChoiceGroupView[]|ChoiceView[] $choices
     * @param mixed $value
     * @return ChoiceView|null
     */
    public function get_value_choice($choices, $value)
    {
        foreach ($this->flatten($choices) as $choice) {
            if (twig_is_selected_choice($choice, $value)) {
                return $choice;
            }
        }
        return null;
    }

    public function first_choice($choices)
    {
        foreach ($this->flatten($choices) as $choice) {
            return $choice;
        }
        return null;
    }

    /**
     * @param ChoiceListView[]|ChoiceGroupView[] $choices
     * @return ChoiceView[]
     */
    protected function flatten($choices)
    {
        $flat = array();
        foreach ($choices as $choice) {
            if (($choice instanceof ChoiceListView) || ($choice instanceof ChoiceGroupView)) {
                $flat = array_merge($flat, $this->flatten($choice->choices));
            } else {
                assert($choice instanceof ChoiceView);
                $flat[] = $choice;
            }
        }
        return $flat;
    }
}

