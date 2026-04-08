<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchRouterFormType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'fields' => array(),
        ));
    }

    private function escapeName($name)
    {
        return str_replace('"', '', $name);
    }

    /**
     * @inheritdoc
     */
    /** Maps legacy Symfony 2 short form type aliases to FQCNs for backward compatibility */
    private static array $typeAliasMap = [
        'checkbox'   => \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class,
        'choice'     => \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
        'collection' => \Symfony\Component\Form\Extension\Core\Type\CollectionType::class,
        'file'       => \Symfony\Component\Form\Extension\Core\Type\FileType::class,
        'hidden'     => \Symfony\Component\Form\Extension\Core\Type\HiddenType::class,
        'integer'    => \Symfony\Component\Form\Extension\Core\Type\IntegerType::class,
        'number'     => \Symfony\Component\Form\Extension\Core\Type\NumberType::class,
        'text'       => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
        'textarea'   => \Symfony\Component\Form\Extension\Core\Type\TextareaType::class,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['fields']['form'] as $name => $conf) {
            $type = $conf['type'];
            $resolvedType = self::$typeAliasMap[$type] ?? $type;
            if (!class_exists($resolvedType)) {
                throw new \RuntimeException("Invalid form type " . $type . " in search configuration");
            }
            $builder->add($this->escapeName($name), $resolvedType, $conf['options'] ?? []);
        }
    }
}
