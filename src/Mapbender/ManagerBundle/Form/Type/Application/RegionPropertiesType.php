<?php


namespace Mapbender\ManagerBundle\Form\Type\Application;


use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegionPropertiesType extends AbstractType
{

    public function getName()
    {
        return 'region_properties';
    }

    public function getParent()
    {
        return 'choice';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // No use ($this) in old PHP...
        $self = $this;
        $resolver->setDefaults(array(
            'application' => null,
            'region' => null,
            'mapped' => false,
            'required' => false,
            'expanded' => true,
            'multiple' => false,
            'choices' => function (Options $options) use ($self) {
                return $self->buildChoices($options);
            },
            'data' => function (Options $options) use ($self) {
                return $self->getData($options);
            }
        ));
    }

    public function buildChoices(Options $options)
    {
        /** @var Application $application */
        $application = $options['application'];
        $templateClassName = $application->getTemplate();
        /** @var Template::class $templateClassName */
        $templateRegionProps = $templateClassName::getRegionsProperties();
        $choices = array();
        if (Kernel::MAJOR_VERSION >= 3 || $options['choices_as_values']) {
            foreach ($templateRegionProps[$options['region']] as $choiceDef) {
                $choices[$choiceDef['label']] = $choiceDef['name'];
            }
        } else {
            foreach ($templateRegionProps[$options['region']] as $choiceDef) {
                $choices[$choiceDef['name']] = $choiceDef['label'];
            }
        }
        return $choices;
    }

    public function getData(Options $options)
    {
        /** @var Application $application */
        $application = $options['application'];
        foreach ($application->getRegionProperties() as $regionProperty) {
            if ($regionProperty->getName() === $options['region']) {
                $values = $regionProperty->getProperties();
                return ArrayUtil::getDefault($values, 'name', null);
            }
        }
        return null;
    }
}
