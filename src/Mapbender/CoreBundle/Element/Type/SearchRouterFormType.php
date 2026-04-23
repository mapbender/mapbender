<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

    /** Maps legacy short form type aliases to FQCNs for backward compatibility */
    private static array $typeAliasMap = [
        'checkbox'   => CheckboxType::class,
        'choice'     => ChoiceType::class,
        'collection' => CollectionType::class,
        'file'       => FileType::class,
        'hidden'     => HiddenType::class,
        'integer'    => IntegerType::class,
        'number'     => NumberType::class,
        'text'       => TextType::class,
        'textarea'   => TextareaType::class,
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
