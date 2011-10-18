<?php
namespace Mapbender\CoreBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * Description of GroupType
 *
 * @author apour
 */
class GroupType extends AbstractType {
	public function getName() {
		return "Group";
	}
	
	public function buildForm(FormBuilder $builder,array $options) {

		$builder->add("name","text",array(
		));
		
		$builder->add("description","textarea",array(
			"required" => false
		));
	}
}
