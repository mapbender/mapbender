<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchRouterFormType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'fields' => array(),
        ));
    }

    private function escapeName($name)
    {
        return str_replace('"', '', $name);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['fields']['form'] as $name => $conf) {
            $type = $conf['type'];
            if (!class_exists($type)) {
                throw new \RuntimeException("Invalid form type " . $type . " in search configuration");
            }
            $builder->add($this->escapeName($name), $type, $conf['options'] ?? []);
        }
    }
}
