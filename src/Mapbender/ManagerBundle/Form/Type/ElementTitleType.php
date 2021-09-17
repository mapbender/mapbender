<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElementTitleType extends AbstractType implements DataTransformerInterface
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\TextType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('element_class');
        $resolver->setAllowedTypes('element_class', 'string');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
    }

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        // Called on norm-to-model transformation.
        // Prevent nulls from reaching Element::setTitle()
        // @todo: make element title column nullable (requires schema update)
        return $value ?: '';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$form->getConfig()->getRequired()) {
            /** @var MinimalInterface|string $elementClass */
            $elementClass = $options['element_class'];
            $attr = array(
                // NOTE: placeholder runs through translation in default form theme
                'placeholder' => $elementClass::getClassTitle(),
            );
            if (!empty($view->vars['attr'])) {
                $attr = $attr + $view->vars['attr'];
            }
            $view->vars['attr'] = $attr;
        }
    }
}
