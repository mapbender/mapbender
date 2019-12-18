<?php
namespace Mapbender\WmcBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WmcType extends AbstractType
{

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
            ->add('state', 'Mapbender\CoreBundle\Form\Type\StateType', array(
                'data_class' => 'Mapbender\CoreBundle\Entity\State',
            ))
            ->add('keywords', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
            ->add('abstract', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
                'required' => false,
            ))
            ->add('logourl', 'Mapbender\WmsBundle\Form\Type\LegendUrlType', array(
                'data_class' => 'Mapbender\WmsBundle\Component\LegendUrl',
            ))
            ->add('screenshot', 'Symfony\Component\Form\Extension\Core\Type\FileType', array(
                'required' => false,
            ))
            ->add('descriptionurl', 'Mapbender\WmsBundle\Form\Type\OnlineResourceType', array(
                'data_class' => 'Mapbender\WmsBundle\Component\OnlineResource',
            ))
        ;
    }

}
