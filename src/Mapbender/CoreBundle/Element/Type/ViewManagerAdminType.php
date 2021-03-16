<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\Kernel;

class ViewManagerAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceOptions = array();
        if (Kernel::MAJOR_VERSION < 3) {
            $choiceOptions['choices_as_values'] = true;
        }
        $accessChoices = array(
            // @todo: translate choice labels
            'Do not show' => '',
            'Read only' => 'ro',
            'Allow saving' => 'rw',
        );
        $builder
           ->add('publicEntries', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $choiceOptions + array(
               // @todo: supply translatable label
               'choices' => $accessChoices,
               'required' => false,
           ))
            ->add('privateEntries', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $choiceOptions + array(
                    // @todo: supply translatable label
                'choices' => $accessChoices,
                'required' => false,
            ))
            ->add('allowAnonymousSave', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                // @todo: supply translatable label
                'required' => false,
            ))
        ;
    }
}
