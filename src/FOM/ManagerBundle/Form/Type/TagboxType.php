<?php

namespace FOM\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagboxType extends AbstractType
{

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_prefix' => '',
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['translation_prefix'] = $options['translation_prefix'];
    }

    public function getBlockPrefix(): string
    {
        return 'tagbox';
    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }
}
