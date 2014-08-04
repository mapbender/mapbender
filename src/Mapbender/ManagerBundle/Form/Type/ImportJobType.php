<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * ImportJobType class creates a form for an ImportJob object.
 */
class ImportJobType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'importjob';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
//            'available_properties' => array(),
//            'data_class' => 'Mapbender\CoreBundle\Entity\RegionProperties'
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'hidden');
        $builder->add('name');
    }

}
