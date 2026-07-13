<?php


namespace Mapbender\ManagerBundle\Form\Type\Application;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateChoiceType extends AbstractType
{
    protected $choices = [];

    public function __construct(ApplicationTemplateRegistry $registry)
    {
        foreach ($registry->getAll() as $template) {
            $this->choices[$template->getTitle()] = $template::class;
        }
        ksort($this->choices);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->choices,
        ]);
    }
}
