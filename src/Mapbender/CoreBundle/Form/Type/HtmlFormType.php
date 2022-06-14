<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Mapbender\CoreBundle\Validator\Constraints\HtmlConstraint;

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
     * @param HtmlConstraint $htmlConstraint
     */
    public function __construct(HtmlConstraint $htmlConstraint)
    {
        $this->htmlConstraint = $htmlConstraint;
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'constraints' => array(
                $this->htmlConstraint,
            )
        ));
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\TextareaType';
    }

}

