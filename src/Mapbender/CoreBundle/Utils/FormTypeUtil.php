<?php


namespace Mapbender\CoreBundle\Utils;


use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpKernel\Kernel;

class FormTypeUtil
{
    /** @var bool|null */
    protected static $v3;
    /** @var bool|null */
    protected static $v4;

    /**
     * Maps (deprecated) Symfony 2 short form type aliases to (forward compatible) FQCNs
     * @var string[]
     */
    protected static $typeAliasMap = array(
        'checkbox' => 'Symfony\Component\Form\Extension\Core\Type\CheckboxType',
        'choice' => 'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
        'collection' => 'Symfony\Component\Form\Extension\Core\Type\CollectionType',
        'entity' => 'Symfony\Bridge\Doctrine\Form\Type\EntityType',
        'file' => 'Symfony\Component\Form\Extension\Core\Type\FileType',
        'hidden' => 'Symfony\Component\Form\Extension\Core\Type\HiddenType',
        'integer' => 'Symfony\Component\Form\Extension\Core\Type\IntegerType',
        'number' => 'Symfony\Component\Form\Extension\Core\Type\NumberType',
        'text' => 'Symfony\Component\Form\Extension\Core\Type\TextType',
        'textarea' => 'Symfony\Component\Form\Extension\Core\Type\TextareaType',
    );

    /**
     * @param string|FormTypeInterface $type
     * @return string
     * @see FormFactoryInterface::create
     */
    public static function migrateFormType($type)
    {
        if (\is_object($type)) {
            $typeName = \get_class($type);
        } else {
            if (!\is_string($type) || !$type) {
                throw new \InvalidArgumentException("Invalid form type " . print_r($type, true));
            }
            if (!empty(static::$typeAliasMap[$type])) {
                $typeName = static::$typeAliasMap[$type];
            } else {
                $typeName = ltrim($type, '\\');
            }
            if (!\is_a($typeName, 'Symfony\Component\Form\FormTypeInterface', true)) {
                throw new \RuntimeException(print_r($typeName, true) . " is not a form type");
            }
        }
        return $typeName;
    }

    /**
     * @param string|FormTypeInterface $type
     * @param array $options
     * @return array
     * @see FormFactoryInterface::create
     */
    public static function migrateFormTypeOptions($type, array $options = array())
    {
        $updatedType = static::migrateFormType($type);
        // If $type itself is Symfony 3+ compatible, assume the $options are as well, and vice versa
        $optionsAreV2 = !\is_string($type) || $type !== $updatedType;
        if (\is_a($updatedType, 'Symfony\Component\Form\Extension\Core\Type\TextType', true)) {
            $options = static::migrateTextOptions($options);
        } elseif (\is_a($updatedType, 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', true)) {
            $options = static::migrateChoiceOptions($options, $optionsAreV2);
        }
        // @todo: others?
        // @todo: can this reasonably be turned into a switch-case?
        return $options;
    }

    /**
     * Detect symfony/forms version 3
     * @return bool
     */
    public static function isV3()
    {
        if (static::$v3 === null) {
            static::$v3 = Kernel::VERSION_ID >= 30000 && Kernel::VERSION_ID < 40000;
        }
        return static::$v3;
    }

    /**
     * Detect symfony/forms version 4
     * @return bool
     */
    public static function isV4()
    {
        if (static::$v4 === null) {
            static::$v4 = Kernel::VERSION_ID >= 40000;
        }
        return static::$v4;
    }

    /**
     * @param array $options
     * @param bool $fromV2
     * @return array
     */
    public static function migrateChoiceOptions(array $options, $fromV2)
    {
        if ($fromV2) {
            $hadChoicesAsValues = !empty($options['choices_as_values']);
            if (!$hadChoicesAsValues && isset($options['choices'])) {
                $options['choices'] = array_flip($options['choices']);
            }
        }
        if (!static::isV4()) {
            $options['choices_as_values'] = true;
        } else {
            unset($options['choices_as_values']);
        }
        return $options;
    }

    public static function migrateTextOptions(array $options)
    {
        // @todo
        return $options;
    }
}
