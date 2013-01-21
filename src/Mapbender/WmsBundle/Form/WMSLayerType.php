<?php
//
//namespace Mapbender\WmsBundle\Form;
//use Symfony\Component\Form\AbstractType;
//use Symfony\Component\Form\FormBuilderInterface;
//
// /**
// * @Deprecated
// */
//class WMSLayerType  extends AbstractType {
//
//    public function getName (){ return "WMSLayer";}
//    public function buildForm(FormBuilderInterface $builder, array $options){
//        $builder->add("title","text",array(
//            "required" => false,
//        ));
//        $builder->add("name","hidden",array(
//            "required" => false,
//        ));
//        $builder->add("abstract","text",array(
//            "required" => false,
//        ));
//        $builder->add("metadataurl","text",array(
//            "required" => false,
//        ));
//        $builder->add("dataurl","text",array(
//            "required" => false,
//        ));
//        $builder->add("srs","hidden",array(
//            "required" => false,
//        ));
//        $builder->add("latLonBounds","hidden",array(
//            "required" => false,
//        ));
//        $builder->add("queryable","hidden",array(
//            "required"  => false,
//        ));
//        $builder->add("cascaded","hidden",array(
//            "required"  => false,
//        ));
//        $builder->add("opaque","hidden",array(
//            "required"  => false,
//        ));
//        $builder->add("noSubset","hidden",array(
//            "required"  => false,
//        ));
////        $builder->add("styles","hidden",array(
////            "required"  => false,
////        ));
//        $builder->add("stylesserialized","hidden",array(
//            "required"  => false,
//        ));
//        $builder->add("fixedWidth","hidden",array(
//            "required"  => false,
//        ));
//        $builder->add("fixedHeight","hidden",array(
//            "required"  => false,
//        ));
//        $builder->add("scaleHintMin","hidden",array(
//            "required"  => false,
//        ));
//        $builder->add("scaleHintMax","hidden",array(
//            "required"  => false,
//        ));
//        $builder->add("layer",'collection',array( 
//            'type' => new WMSLayerType(),
//        ));
//
//    }
////    public function getDefaultOptions(){
////        return array(
////            'data_class' => "Mapbender\WmsBundle\Entity\WMSLayer"
////        );
////   }
//}
