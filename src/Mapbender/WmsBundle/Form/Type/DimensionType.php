<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Form\Type\OnlineResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * WmsSourceSimpleType class
 */
class DimensionType extends AbstractType
{

    protected $name;
    protected $parent;

    public function __construct($name, $parent)
    {
        $this->name = $name;
        $this->parent = $parent;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array('required' => true))
            ->add('units', 'text', array('required' => false))
            ->add('unitSymbol', 'text', array('required' => false))
            ->add('default', 'text', array('required' => false))
            ->add('multipleValues', 'checkbox', array('required' => false))
            ->add('nearestValue', 'checkbox', array('required' => false))
            ->add('current', 'checkbox', array('required' => false));
    }

}
