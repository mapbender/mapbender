<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Mapbender\CoreBundle\Validator\Constraints\HtmlConstraint;
use Mapbender\CoreBundle\Validator\Constraints\TwigConstraint;

/**
 * 
 */
class HTMLElementAdminType extends AbstractType
{
    /**
     * @var HtmlConstraint
     */
    private $htmlConstraint;

    /**
     * @var TwigConstraint
     */
    private $twigConstraint;

    /**
     * HTMLElementAdminType constructor
     *
     * @param HtmlConstraint $htmlConstraint
     * @param TwigConstraint $twigConstraint
     */
    public function __construct(HtmlConstraint $htmlConstraint, TwigConstraint $twigConstraint)
    {
        $this->htmlConstraint = $htmlConstraint;
        $this->twigConstraint = $twigConstraint;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'htmlelement';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        $builder
            ->add('content', 'textarea', [
                'required' => false,
                'constraints' => [
                    $this->htmlConstraint,
                    $this->twigConstraint,
                ]
            ])
            ->add('classes', 'text', [
                'required' => false,
            ]);
    }
}
