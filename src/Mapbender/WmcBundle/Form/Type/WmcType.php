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
            ->add('id', 'Symfony\Component\Form\Extension\Core\Type\HiddenType')
            ->add('public', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.wmc.wmceditor.publish',
            ))
            ->add('state', new StateType(), array(
                'data_class' => 'Mapbender\CoreBundle\Entity\State',
            ))
            ->add('keywords', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('abstract', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
                'required' => false,
            ))
            ->add('logourl', new LegendUrlType(), array(
                'data_class' => 'Mapbender\WmsBundle\Component\LegendUrl',
            ))
            ->add('screenshot', 'Symfony\Component\Form\Extension\Core\Type\FileType', array(
                'required' => false,
            ))
            ->add('descriptionurl', new OnlineResourceType(), array(
                'data_class' => 'Mapbender\WmsBundle\Component\OnlineResource',
            ))
        ;
    }

}
