<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Mapbender\CoreBundle\Validator\Constraints\HtmlConstraint;
use Mapbender\CoreBundle\Validator\Constraints\TwigConstraint;

/**
 * Class HtmlFormType
 * @package Mapbender\CoreBundle\Form\Type
 */
class HtmlFormType extends AbstractType
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'constraints' => array(
                $this->htmlConstraint,
                $this->twigConstraint,
            )
        ));
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'html';
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\TextareaType';
    }

}

