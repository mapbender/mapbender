<?php
namespace Mapbender\WmcBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WmcLoadType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmcload';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('xml', 'file', array('required' => true));
    }

}
