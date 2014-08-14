<?php
namespace Mapbender\CoreBundle\Element\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class SourceSetAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'sourcesset';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'instances' => new ArrayCollection()
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text',
                array(
                'required' => true,
                'property_path' => '[title]'))
            ->add('group', 'text',
                array(
                'required' => false,
                'property_path' => '[group]'))
            ->add('instances', 'entity', array(
                'class' => 'Mapbender\\CoreBundle\\Entity\\SourceInstance',
                'property' => 'title',
                'property_path' => '[instances]',
                'group_by' => 'title',
//                'choices' => $options['sources'],
                'required' => true,
                'multiple' => true));
    }

}