<?php


namespace Mapbender\ManagerBundle\Form\Type\Application;


use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegionPropertiesType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'compound' => true,
            'label_attr' => array(
                'class' => 'hidden',
            ),
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Application $application */
        $application = $options['application'];
        $templateClass = $application->getTemplate();
        // Guard against empty template (creating new Application)
        if ($templateClass) {
            /** @var string|Template $templateClass */
            foreach ($templateClass::getRegions() as $regionName) {
                $formType = $templateClass::getRegionSettingsFormType($regionName);
                if ($formType) {
                    $builder->add($regionName, $formType);
                }
            }
        }
        $builder->setDataMapper(new RegionPropertiesMapper());
    }
}
