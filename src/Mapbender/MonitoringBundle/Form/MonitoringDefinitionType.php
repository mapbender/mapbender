<?php
namespace Mapbender\MonitoringBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Description of MonitoringDefinitionType
 *
 * @author apour
 */
class MonitoringDefinitionType extends AbstractType {
	public function getName() {
		return "MonitoringDefinition";
	}
	
	public function buildForm(FormBuilderInterface $builder,array $options) {
//		$builder->add("type","",array(
//			"required" => false
//		));
//		
//		$builder->add("typeId","",array(
//			"required" => false
//		));
		
		$builder->add("name","text",array(
			"required" => false
		));
		
		$builder->add("title","text",array(
			"required" => false
		));
		
		$builder->add("alias","text",array(
			"required" => false
		));
		
		$builder->add("url","url",array(
		));
		
		$builder->add("requestUrl","textarea",array(
		));
		
//		$builder->add("response","",array(
//			"required" => false
//		));
//		
//		$builder->add("lastResponse","",array(
//			"required" => false
//		));
		
//		$builder->add("contactEmail","",array(
//			"required" => false
//		));
		
//		$builder->add("contact","text",array(
//			"required" => false
//		));
		
//		$builder->add("lastNotificationTime","",array(
//			"required" => false
//		));
//		
//		$builder->add("ruleStart","time",array(
//			"required" => false
//		));
//		
//		$builder->add("ruleEnd","time",array(
//			"required" => false
//		));
//		
//		$builder->add("ruleMonitor","choice",array(
//			'choices'   => array('0' => 'disallow', '1' => 'allow'),
//			'preferred_choices' => array('allow'),
//			"required" => false
//		));
		
		$builder->add("enabled","choice",array(
			'choices'   => array('0' => 'false', '1' => 'true'),
			'preferred_choices' => array('true'),
			"required" => false
		));
	}
}
