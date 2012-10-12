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
        $view->getChild(0)->set('label', 'min x')->set('attr', array('placeholder' => 'min x'));
        $view->getChild(1)->set('label', 'min y')->set('attr', array('placeholder' => 'min y'));
        $view->getChild(2)->set('label', 'max x')->set('attr', array('placeholder' => 'max x'));
        $view->getChild(3)->set('label', 'max y')->set('attr', array('placeholder' => 'max y'));
    }
}

