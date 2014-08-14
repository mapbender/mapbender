<?php

namespace Mapbender\CoreBundle\Element\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\ExtendedCollection;
use Mapbender\CoreBundle\Form\EventListener\BaseSourceSwitcherFieldSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class BaseSourceSwitcherAdminType extends AbstractType implements ExtendedCollection
{

    public $hasSubForm = true;

    public function isSubForm()
    {
        return $this->hasSubForm;
    }

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
            'element' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $application = $options["application"];
        $element = $options["element"];
        $instAC = new ArrayCollection();
        if ($element !== null && $element->getId() !== null) {
            foreach ($application->getElements() as $appl_element) {
                $configuration = $element->getConfiguration();
                if ($appl_element->getId() === intval($configuration["target"])) {
                    $mapconfig = $appl_element->getConfiguration();
                    foreach ($application->getLayersets() as $layerset) {
                        if (intval($mapconfig['layerset']) === $layerset->getId()) {
                            foreach ($layerset->getInstances() as $instance) {
                                if ($instance->getEnabled() && $instance->isBaseSource() && !$instAC->contains($instance)){
                                    $instAC->add($instance);
                                }
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        }
        $builder->add('title', 'text', array('required' => true))
            ->add('tooltip', 'text', array('required' => false))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $application,
                'property_path' => '[target]',
                'required' => false));
//        if ($element !== null && $element->getId() !== null) {
            $builder->add('instancesets', "collection", array(
                'property_path' => '[instancesets]',
                'type' => new SourceSetAdminType(),
                'allow_add' => true,
                'allow_delete' => true,
                'auto_initialize' => false,
                'options' => array(
                    'instances' => $instAC
            )));
//        }
    }

}
