<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SearchRouterFormType extends AbstractType
{
    public function getName() {
        return 'search_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'fields' => array()));
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        foreach($options['fields']['form'] as $name => $conf) {
            $conf = array_merge_recursive(array(
                'options' => array(
                    'required' => false)),
                $conf);

            $builder->add($name, $conf['type'], $conf['options']);
        }
    }
}
