<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class PositionType extends AbstractType
{
    public function getName()
    {
        return 'position';
    }

    public function getParent()
    {
        return 'collection';
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->getChild(0)->set('label', 'left')->set('attr', array('placeholder' => 'left'));
        $view->getChild(1)->set('label', 'top')->set('attr', array('placeholder' => 'top'));
    }
}

