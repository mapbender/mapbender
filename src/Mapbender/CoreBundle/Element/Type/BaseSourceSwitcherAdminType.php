<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Form\EventListener\BaseSourceSwitcherFieldSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\CoreBundle\Component\ExtendedCollection;

/**
 * 
 */
class BaseSourceSwitcherAdminType extends AbstractType implements ExtendedCollection
{
    public $hasSubForm = true;
    
    public function isSubForm(){
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
        $instList = array("" => " ");
        if ($element !== null && $element->getId() !== null) {
            foreach ($application->getElements() as $appl_element) {
                $configuration = $element->getConfiguration();
                if ($appl_element->getId() === intval($configuration["target"])) {
                    $mapconfig = $appl_element->getConfiguration();
                    foreach ($application->getLayersets() as $layerset) {
                        if (intval($mapconfig['layerset']) === $layerset->getId()) {
                            foreach ($layerset->getInstances() as $instance) {
                                if ($instance->getEnabled())
                                        $instList[strval($instance->getId())] = $instance->getTitle();
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        }
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $application,
                'property_path' => '[target]',
                'required' => false));
        if ($element !== null && $element->getId() !== null) {
            $builder->add('sourcesets', "collection",
                array(
                'property_path' => '[sourcesets]',
                'type' => new SourceSetAdminType(),
                'allow_add' => true,
                'allow_delete' => true,
                'options' => array(
                    'sources' => $instList
            )));
        }
    }

}