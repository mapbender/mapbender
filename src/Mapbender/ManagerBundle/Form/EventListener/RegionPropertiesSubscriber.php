<?php
namespace Mapbender\ManagerBundle\Form\EventListener;

use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Mapbender\ManagerBundle\Form\Type\RegionType;
use Mapbender\ManagerBundle\Form\Type\PropertiesType;

//use Mapbender\CoreBundle\Element\Type\PrintClientQualityAdminType;

/**
 * 
 */
class RegionPropertiesSubscriber implements EventSubscriberInterface
{
    /**
     * A FormFactoryInterface 's Factory
     * 
     * @var \Symfony\Component\Form\FormFactoryInterface 
     */
    private $factory;

    /**
     * The application
     * 
     * @var application
     */
    private $options;

    /**
     * Creates a subscriber
     * 
     * @param \Symfony\Component\Form\FormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory, $options)
    {
        $this->factory = $factory;
        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_BIND => 'preBind');
    }

    /**
     * Checkt form fields by PRE_BIND DataEvent
     * 
     * @param DataEvent $event
     */
    public function preBind(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (null === $data) {
            return;
        }
        if (key_exists("name", $data) && isset($this->options['available_properties'][$data['name']])) {
            $choices = array();
            foreach ($this->options['available_properties'][$data['name']] as
                    $key => $value) {
                $choices[$key] = $key;
            }
            $form->add($this->factory->createNamed(
                    'properties', "choice", null,
                    array(
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => $choices
            )));
        }
//        $appl = $this->options['data'];
//        $templateDefaultProperties = array();
//        if(key_exists("name", $data))
//        {
//            $templateClass = $data['template'];//();
//            $templateDefaultProperties = $templateClass::getDefaultProperties();
////        $template = new $templateClass($this->container,
////            $this->get('mapbender')->getApplication($slug, array()));
////            $a = 0;
////            $data["scales"] = preg_split("/\s?,\s?/", $data["scales"]);
////            $event->setData($data);
//        }
//        if(!key_exists("templateProperties", $data))
//        {
//        }
//            $form->add($this->factory->createNamed(
//                                'templateProperties', "collection", null,
//                                array(
//                            'property_path' => 'templateProperties',
//                            'type' => new RegionType(),
//                            'options' => $templateDefaultProperties)));
//        if(key_exists("templates", $data) )
//        {
//            $form->add($this->factory->createNamed(
//                                'templates', "collection", null,
//                                array(
//                            'property_path' => '[templates]',
//                            'type' => new PrintClientTemplateAdminType(),
//                            'options' => array(
//                                ))));
//        }
//        if(key_exists("quality_levels", $data) )
//        {
//            $form->add($this->factory->createNamed(
//                                'quality_levels', "collection", null,
//                                array(
//                            'property_path' => '[quality_levels]',
//                            'type' => new PrintClientQualityAdminType(),
//                            'options' => array(
//                                ))));
//        }
    }

    /**
     * Checkt form fields by PRE_SET_DATA DataEvent
     * 
     * @param DataEvent $event
     */
    public function preSetData(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (null === $data) {
            return;
        }
        if ($data->getName() !== null && isset($this->options['available_properties'][$data->getName()])) {
            $choices = array();
            foreach ($this->options['available_properties'][$data->getName()] as
                    $key => $value) {
                $choices[$key] = $key;
            }
            $form->add($this->factory->createNamed(
                    'properties', "choice", null,
                    array(
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => $choices
            )));
        }
//        $appl = $this->options['data'];
//        if($appl->getTemplate() !== null)// && is_array($data["templateProperties"]))
//        {
//            $templClass = $appl->getTemplate();
//            $templateDefaultProperties = $templClass::getRegionsProperties();
////            $a = 0;
////            ->add('templateProperties', 'collection',
////                array(
////                'type' => new RegionType(),
//////                'property_path' => '[templateProperties]',
////                'options' => $options['available_region_properties']))
////            $form->add($this->factory->createNamed(
////                                'templateProperties', "collection", null,
////                                array(
////                            'property_path' => '[templateProperties]',
////                            'type' => new PropertiesType(),
////                            'options' => $templateDefaultProperties['sidepane']//$this->application['available_region_properties']
////                )));
////            $a = 0;
////            $data["scales"] = implode(",", $data["scales"]);
////            $event->setData($data);
//        }
//        if(key_exists("templates", $data) )
//        {
//            $form->add($this->factory->createNamed(
//                                'templates', "collection", null,
//                                array(
//                            'property_path' => '[templates]',
//                            'type' => new PrintClientTemplateAdminType(),
//                            'options' => array(
//                                ))));
//        }
//        
//        if(key_exists("quality_levels", $data) )
//        {
//            $form->add($this->factory->createNamed(
//                                'quality_levels', "collection", null,
//                                array(
//                            'property_path' => '[quality_levels]',
//                            'type' => new PrintClientQualityAdminType(),
//                            'options' => array(
//                                ))));
//        }
    }

}