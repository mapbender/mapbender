<?php
namespace Mapbender\WmcBundle\Form\Type;

use Mapbender\CoreBundle\Form\Type\StateType;
use Mapbender\WmsBundle\Form\Type\LegendUrlType;
use Mapbender\WmsBundle\Form\Type\OnlineResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WmcType extends AbstractType
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
        $builder
            ->add('id', 'hidden')
            ->add('public', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wmc.wmceditor.publish',
            ))
            ->add('state', new StateType(), array(
                'data_class' => 'Mapbender\CoreBundle\Entity\State',
            ))
            ->add('keywords', 'text', array(
                'required' => false,
            ))
            ->add('abstract', 'textarea', array(
                'required' => false,
            ))
            ->add('logourl', new LegendUrlType(), array(
                'data_class' => 'Mapbender\WmsBundle\Component\LegendUrl',
            ))
            ->add('screenshot', 'file', array(
                'required' => false,
            ))
            ->add('descriptionurl', new OnlineResourceType(), array(
                'data_class' => 'Mapbender\WmsBundle\Component\OnlineResource',
            ))
        ;
    }

}
