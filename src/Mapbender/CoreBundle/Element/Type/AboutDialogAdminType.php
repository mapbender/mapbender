<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AboutDialogAdminType extends AbstractType
{

    public function getParent()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseButtonAdminType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Icon is hard-coded, remove upstream icon field.
        // @todo: allow configuration, after providing the previously hard-coded setting as a default
        if ($builder->has('icon')) {
            $builder->remove('icon');
        }
    }
}
