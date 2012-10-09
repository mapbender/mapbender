<?php
namespace Mapbender\MonitoringBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Description of MonitoringDefinitionType
 *
 * @author apour
 */
class SchedulerProfileType extends AbstractType {
    
    protected $scheduler;
    
    public function __construct($scheduler){
        $this->scheduler = $scheduler;
    }
    
	public function getName() {
		return "SchedulerProfile";
	}
	
	public function buildForm(FormBuilderInterface $builder,array $options) {
//        translator = $this->get('translator');
        $builder->add('title', 'text', array(
                    'label' => 'Title',
                    'required'  => true))
                ->add('starttime', 'time', array(
                    'label' => 'Start Time',
                    'required'  => true));
        $startintervalops = $this->scheduler->getStarttimeintervalOpts();
        $keys = array_keys($startintervalops);
        foreach ($keys as $key){
            $startintervalops[$key] = $startintervalops[$key];
        }
        $builder->add('starttimeinterval', 'choice', array(
                    'label' => 'Delay',
                    'choices' => $startintervalops,
                    'required'  => true));
//        $jobcontinuityops = $this->scheduler->getJobcontinuityOpts();
//        $keys = array_keys($jobcontinuityops);
//        foreach ($keys as $key){
//            $jobcontinuityops[$key] = $jobcontinuityops[$key];
//        }
//        $builder->add('jobcontinuity', 'choice', array(
//                    'label' => 'Estimated Job Continuity',
//                    'choices' => $jobcontinuityops,
//                    'required'  => true));
        $jobintervalops = $this->scheduler->getJobintervalOpts();
        $keys = array_keys($jobintervalops);
        foreach ($keys as $key){
            $jobintervalops[$key] = $jobintervalops[$key];
        }
        $builder->add('jobinterval', 'choice', array(
                    'label' => 'Delay Between The Queries',
                    'choices' => $jobintervalops,
                    'required'  => true))
                ->add('current', 'checkbox', array(
                        'label' => 'Activated',
                        'required'  => false));
    }
}
