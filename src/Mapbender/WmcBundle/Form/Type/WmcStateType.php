<?php
namespace Mapbender\WmcBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WmcStateType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmcstate';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('state', 'hidden', array(
            'required' => false,
            'data_class' => 'Mapbender\CoreBundle\Entity\State',
        ));
    }

}
