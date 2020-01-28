<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayersetAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        // NOTE: alias is no longer used inside Mapbender, but is maintained
        //       for compatibility with very, very common OverviewAdminType customizations
        //       To minimize issues on planned / future Symfony upgrades, newly written
        //       code should use the FQCN, instead of the alias name, to reference
        //       this type,
        return 'app_layerset';
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return 'Mapbender\CoreBundle\Form\Type\OrderAwareMultipleChoiceType';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'choices' => function(Options $options) {
                /** @var Application $application */
                $application = $options['application'];
                $choices = array();
                foreach ($application->getLayersets() as $layerset) {
                    $choices[$layerset->getTitle()] = $layerset->getId();
                }
                if ($options->offsetExists('choices_as_values') && !$options['choices_as_values']) {
                    return array_flip($choices);
                } else {
                    return $choices;
                }
            },
            'choices_as_values' => true,
        ));
    }
}
