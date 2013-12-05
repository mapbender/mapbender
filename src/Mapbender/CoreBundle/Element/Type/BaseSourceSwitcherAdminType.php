<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Form\EventListener\BaseSourceSwitcherFieldSubscriber;
use Mapbender\CoreBundle\Form\Type\PositionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class BaseSourceSwitcherAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'basesourceswitcher';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new BaseSourceSwitcherFieldSubscriber($builder->getFormFactory(),
            $options["application"]);
        $builder->addEventSubscriber($subscriber);
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
//                ->add('anchor', "choice",
//                      array(
//                    'required' => true,
//                    "choices" => array(
//                        'inline' => 'inline',
//                        'left-top' => 'left-top',
//                        'left-bottom' => 'left-bottom',
//                        'right-top' => 'right-top',
//                        'right-bottom' => 'right-bottom')))
//                ->add('position', new PositionType(),
//                      array(
//                    'label' => 'Position',
//                    'property_path' => '[position]'))
//                ->add('fullscreen', "checkbox", array('required' => false))
        ;
    }

}