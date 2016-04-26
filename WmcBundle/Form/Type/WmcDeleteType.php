<?php
namespace Mapbender\WmcBundle\Form\Type;

use Mapbender\CoreBundle\Form\Type\StateType;
use Mapbender\WmsBundle\Form\Type\LegendUrlType;
use Mapbender\WmsBundle\Form\Type\OnlineResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

//use Symfony\Component\Form\FormBuilder;

class WmcDeleteType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmc';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'hidden');
    }

}
