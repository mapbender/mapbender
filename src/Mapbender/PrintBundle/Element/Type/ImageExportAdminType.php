<?php
namespace Mapbender\PrintBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageExportAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('target', 'Mapbender\CoreBundle\Element\Type\TargetElementType', array(
            'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
            'application' => $options['application'],
            'required' => false,
        ));
    }

}
