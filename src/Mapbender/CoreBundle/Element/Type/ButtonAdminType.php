<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *
 */
class ButtonAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'button';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('icon', new IconClassType(), array('required' => false))
            ->add('label', 'checkbox', array('required' => false))
            ->add('target', 'target_element',
                array(
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('click', 'text', array('required' => false))
            ->add('group', 'text', array('required' => false))
            ->add('action', 'text', array('required' => false))
            ->add('deactivate', 'text', array('required' => false));
    }

}
