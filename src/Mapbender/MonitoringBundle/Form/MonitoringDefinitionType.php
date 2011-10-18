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
		$builder->add("title","text",array(
			"required" => false
		));
		
		$builder->add("url","url",array(
		));
		
		$builder->add("requestUrl","textarea",array(
		));
	}
}