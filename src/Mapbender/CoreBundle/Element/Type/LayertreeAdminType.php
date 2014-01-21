<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Component\ExtendedCollection;
use Mapbender\CoreBundle\Element\Type\SourceSetAdminType;
use Mapbender\CoreBundle\Form\DataTransformer\ObjectIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

/**
 * 
 */
class LayertreeAdminType extends AbstractType implements ExtendedCollection
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'layertree_sources';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'element' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $application = $options["application"];
        $element = isset($options["element"]) ? $options["element"] : null;
        $target_layerset = null;
        if ($element !== null && $element->getId() !== null) {
            foreach ($application->getElements() as $appl_element) {
                $configuration = $element->getConfiguration();
                if ($appl_element->getId() === intval($configuration["target"])) {
                    $mapconfig = $appl_element->getConfiguration();
                    foreach ($application->getLayersets() as $layerset) {
                        if (intval($mapconfig['layerset']) === $layerset->getId()) {
                            $target_layerset = $layerset;
                            break;
                        }
                    }
                    break;
                }
            }
        }
        $builder->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('type', 'choice',
                array(
                'required' => true,
                'choices' => array('dialog' => 'Dialog', 'element' => 'Element')))
            ->add('autoOpen', 'checkbox',
                array(
                'required' => false))
            ->add('displaytype', 'choice',
                array(
                'required' => true,
                'choices' => array('tree' => 'Tree')))
            ->add('titlemaxlength', 'text', array('required' => true))
            ->add('showBaseSource', 'checkbox',
                array(
                'required' => false))
            ->add('layerMenu', 'checkbox',
                array(
                'required' => false))
            ->add('layerRemove', 'checkbox',
                array(
                'required' => false))
            ->add('showHeader', 'checkbox',
                array(
                'required' => false));
        if ($target_layerset !== null) {
            $builder->add('baseSources', "layerset_sources",
                array(
                'application' => $options['application'],
                'target_layerset' => $target_layerset,
                'multiple' => true,
                'property_path' => '[baseSources]',
                'required' => false));
        }
    }

}