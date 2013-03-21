<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\CoreBundle\Form\Type\PositionType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\CoreBundle\Form\Type\ExtentType;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Element\DataTranformer\LayersetNameTranformer;

/**
 * 
 */
class OverviewAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'overview';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $app = $options['application'];
        $layersets = array();
        foreach($app->getLayersets() as $layerset)
        {
            $layersets[$layerset->getId()] = $layerset->getTitle();
        }

        $builder->add('tooltip', 'text', array('required' => false))
                ->add('layerset', 'choice',
                      array(
                    'label' => 'Layerset',
                    "required" => true,
                    'choices' => $layersets))
                ->add('target', 'target_element',
                      array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application' => $options['application'],
                    'property_path' => '[target]',
                    'required' => false))
                ->add('anchor', "choice",
                      array(
                    'required' => true,
                    "choices" => array(
                        'inline' => 'inline',
                        'left-top' => 'left-top',
                        'left-bottom' => 'left-bottom',
                        'right-top' => 'right-top',
                        'right-bottom' => 'right-bottom')))
                ->add('position', new PositionType(),
                      array(
                    'label' => 'Position',
                    'property_path' => '[position]'))
                ->add('maximized', 'checkbox', array('required' => false))
                ->add('fixed', 'checkbox', array('required' => false))
                ->add('width', 'text', array('required' => true))
                ->add('height', 'text', array('required' => true));
    }

}