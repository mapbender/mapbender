<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class PositionXYType extends AbstractType
{
    public function getName()
    {
        return 'positionxy';
    }

    public function getParent()
    {
        return 'collection';
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->getChild(0)->set('label', 'x')->set('attr', array('placeholder' => 'x'));
        $view->getChild(1)->set('label', 'y')->set('attr', array('placeholder' => 'y'));
    }
}

