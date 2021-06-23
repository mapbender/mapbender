<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HttpSourceSelectionType extends HttpSourceOriginType
{
    protected $choices;

    public function __construct(TypeDirectoryService $typeDirectory)
    {
        $this->choices = array_flip($typeDirectory->getTypeLabels());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'data_class' => 'Mapbender\ManagerBundle\Form\Model\HttpOriginModel',
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fieldOptions = array(
            // HACK: use "type" translation from Element scope
            'label' => 'mb.manager.admin.element.type',
            'required' => true,
            'mapped' => false,
        );
        if (count($this->choices) >= 1) {
            $builder->add('type', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $fieldOptions + array(
                'choices' => $this->choices,
            ));
        } else {
            $values = array_values($this->choices);
            $builder->add('type', 'Symfony\Component\Form\Extension\Core\Type\HiddenType', $fieldOptions + array(
                'data' => $values[0],
            ));
        }
        parent::buildForm($builder, $options);
    }
}
