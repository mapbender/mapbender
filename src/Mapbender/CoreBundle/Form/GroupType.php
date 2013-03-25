<?php

namespace Mapbender\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Description of GroupType
 *
 * @author Arash R. Pour
 */
class GroupType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return "Group";
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add("name", "text", array(
        ));

        $builder->add("description", "textarea",
                      array(
            "required" => false
        ));
    }

}
