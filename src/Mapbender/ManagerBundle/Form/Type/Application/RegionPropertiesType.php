<?php


namespace Mapbender\ManagerBundle\Form\Type\Application;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegionPropertiesType extends AbstractType
{
    /** @var ApplicationTemplateRegistry */
    protected $templateRegistry;

    public function __construct(ApplicationTemplateRegistry $templateRegistry)
    {
        $this->templateRegistry = $templateRegistry;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $templateRegistry = $this->templateRegistry;
        $resolver->setDefaults(array(
            'application' => null,
            'compound' => true,
            'label_attr' => array(
                'class' => 'hidden',
            ),
            'region_names' => function (Options $options) use ($templateRegistry) {
                /** @var Application $application */
                $application = $options['application'];
                $template = $templateRegistry->getApplicationTemplate($application);
                // Guard against empty template (creating new Application)
                if ($template) {
                    return $template->getRegions() ?: array();
                } else {
                    return array();
                }
            }
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Application $application */
        $application = $options['application'];
        $template = $this->templateRegistry->getApplicationTemplate($application);
        // Guard against empty template (creating new Application)
        if ($template) {
            foreach ($options['region_names'] as $regionName) {
                $formType = $template->getRegionSettingsFormType($regionName);
                if ($formType) {
                    $builder->add($regionName, $formType);
                }
            }
        }
        $builder->setDataMapper(new RegionPropertiesMapper($this->templateRegistry));
    }
}
