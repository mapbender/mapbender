<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class ExtentType extends AbstractType
{
    public function getName()
    {
        return 'extent';
    }

    public function getParent()
    {
        return 'collection';
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->children[0]->vars['label'] = 'min x';
        $view->children[0]->vars['attr'] = array('placeholder' => 'min x');
        $view->children[1]->vars['label'] = 'min y';
        $view->children[1]->vars['attr'] = array('placeholder' => 'min y');
        $view->children[2]->vars['label'] = 'max x';
        $view->children[2]->vars['attr'] = array('placeholder' => 'max x');
        $view->children[3]->vars['label'] = 'max y';
        $view->children[3]->vars['attr'] = array('placeholder' => 'max y');
    }
}

