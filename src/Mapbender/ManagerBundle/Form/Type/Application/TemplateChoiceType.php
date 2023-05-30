<?php


namespace Mapbender\ManagerBundle\Form\Type\Application;


use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateChoiceType extends AbstractType
{
    protected $choices = array();

    public function __construct(ApplicationTemplateRegistry $registry)
    {
        foreach ($registry->getAll() as $template) {
            $this->choices[$template->getTitle()] = \get_class($template);
        }
        ksort($this->choices);
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->choices,
        ));
    }
}
