<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * 
 */
class ScaleBarAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'scalebar';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
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
        foreach ($app->getLayersets() as $layerset) {
            $layersets[$layerset->getId()] = $layerset->getTitle();
        }

        $builder->add('tooltip', 'text', array('required' => false))
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('maxWidth', 'text', array('required' => true))
            ->add('anchor', "choice",
                array(
                'required' => true,
                "choices" => array(
                    'left-top' => 'left-top',
                    'left-bottom' => 'left-bottom',
                    'right-top' => 'right-top',
                    'right-bottom' => 'right-bottom')))
            ->add('units', 'choice',
                array(
                'required' => true,
                'multiple' => true,
                'choices' => array(
                    'km' => 'kilometer',
                    'ml' => 'mile')));
    }

}