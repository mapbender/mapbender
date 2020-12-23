<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

class ElementTitleType extends AbstractType implements DataTransformerInterface
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\TextType';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
    }

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        // Called on norm-to-model transformation.
        // Prevent nulls from reaching Element::setTitle()
        // @todo: make element title column nullable (requires schema update)
        return $value || '';
    }
}
