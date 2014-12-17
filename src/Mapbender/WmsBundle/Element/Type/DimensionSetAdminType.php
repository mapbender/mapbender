<?php

namespace Mapbender\WmsBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class DimensionSetAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'dimensionset';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'dimensions' => array()
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dimChioces = array();
        $dimJson = array();
        foreach ($options['dimensions'] as $instId => $dims) {
            $dimJson[$instId] = array();
            foreach ($dims as $dim) {
                $dimChioces[$instId . "-" . $dim->getName() . "-" . $dim->getType()] = $instId . "-" . $dim->getName() . "-" . $dim->getType();
                $dimJson[$instId][] = $dim->getConfiguration();
            }
        }
        $builder->add('title', 'text',
                      array(
                'required' => true,
                'property_path' => '[title]'))
            ->add('group', 'choice',
                  array(
                'required' => true,
                'choices' => $dimChioces,
                'multiple' => true,
                'mapped' => false,
                'attr' => array(
                    'data-dimension-group' => json_encode($dimJson))))
            ->add('dimension', new DimensionInstElmType(), array(
                'required' => false,));
    }

}
