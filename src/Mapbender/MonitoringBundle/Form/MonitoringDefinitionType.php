<?php
namespace Mapbender\MonitoringBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * Description of MonitoringDefinitionType
 *
 * @author apour
 */
class MonitoringDefinitionType extends AbstractType {
	public function getName() {
		return "MonitoringDefinition";
	}
	
	public function buildForm(FormBuilder $builder,array $options) {
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
		
		$builder->add("ruleStart","text",array(
			"required" => false
		));
		
		$builder->add("ruleEnd","text",array(
			"required" => false
		));
		
		$builder->add("ruleMonitor","choice",array(
			'choices'   => array('0' => 'allow', '1' => 'disallow'),
			'preferred_choices' => array('allow'),
			"required" => false
		));
		
		$builder->add("enabled","choice",array(
			'choices'   => array('0' => 'true', '1' => 'false'),
			'preferred_choices' => array('true'),
			"required" => false
		));
	}
}
