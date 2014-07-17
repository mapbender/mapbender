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
        $view->children[0]->vars['label'] = 'x';
        $view->children[0]->vars['attr'] = array('placeholder' => 'x');
        $view->children[1]->vars['label'] = 'y';
        $view->children[1]->vars['attr'] = array('placeholder' => 'y');
    }
}

