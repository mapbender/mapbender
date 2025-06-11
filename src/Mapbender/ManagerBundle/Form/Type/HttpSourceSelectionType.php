<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HttpSourceSelectionType extends HttpSourceOriginType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'data_class' => HttpOriginModel::class,
        ));
    }
}
