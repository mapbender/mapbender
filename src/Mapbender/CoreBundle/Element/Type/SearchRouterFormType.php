<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *
 */
class SearchRouterFormType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'search_form';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'fields' => array()));
    }

    private function escapeName($name)
    {
        return str_replace('"', '', $name);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['fields']['form'] as $name => $conf) {
            $builder->add($this->escapeName($name), $conf['type'], $conf['options']);
        }
    }
}
