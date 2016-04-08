<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\CoreBundle\Form\Type\StrokeType;
use Mapbender\CoreBundle\Element\DataTransformer\PaintTransformer;

class PaintType extends AbstractType
{
    public function getName()
    {
        return 'paint';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('stroke', new StrokeType(), array())
            ->add('fill', new FillType(), array())
            ->add('point', new PointType(), array())
            ->addModelTransformer(new PaintTransformer());
    }
}
