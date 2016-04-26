<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ApplicationCopyType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'application';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Base data
            ->add('title', 'text', array(
                'attr' => array(
                    'title' => 'The application title, as shown in the browser '
                        . 'title bar and in lists.')))
            ->add('slug', 'text', array(
                'label' => 'URL title',
                'attr' => array(
                    'title' => 'The URL title (slug) is based on the title and used in the '
                        . 'application URL.')))
            ->add('description', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'title' => 'The description is used in overview lists.')));
    }

}

