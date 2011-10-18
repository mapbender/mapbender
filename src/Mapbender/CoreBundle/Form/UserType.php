<?php
namespace Mapbender\CoreBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * Description of UserType
 *
 * @author apour
 */
class UserType extends AbstractType {
	public function getName() {
		return "User";
	}
	
	public function buildForm(FormBuilder $builder,array $options) {
		$builder->add("username","text",array(
			"required" => true
		));

		$builder->add('password', 'repeated', array(
			'type' => 'password',
			'invalid_message' => 'The password fields must match.',
			'options' => array('label' => 'Password'),
		));


		$builder->add("email","email",array(
			"required" => true
		));
		
		$builder->add("firstName","text",array(
			"required" => false
		));
		
		$builder->add("lastName","text",array(
			"required" => false
		));
		
		$builder->add("displayName","text",array(
			"required" => false
		));
	}
}
