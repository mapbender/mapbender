<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\ManagerBundle\Form\DataTransformer\YAMLDataTransformer;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class YAMLConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new YAMLDataTransformer(jsonEncode: $options['json_encode'] ?? false));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->preSubmit(...));
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function preSubmit(FormEvent $event): void
    {
        try {
            Yaml::parse($event->getData() ?: '');
        } catch (ParseException $e) {
            $event->getForm()->addError(new FormError($e->getMessage()));
            // prevent further processing
            throw new TransformationFailedException($e->getMessage(), 0, $e);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'attr' => [
                'class' => 'code-yaml',
            ],
            'json_encode' => false,
        ]);
    }
}
