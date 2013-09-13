<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SourceDeleteType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
	return 'sourcedelete';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	$builder->add("id", "hidden", array("required" => false));
    }

}

