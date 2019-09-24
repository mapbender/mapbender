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
        return 'app_layerset';
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'class' => 'MapbenderCoreBundle:Layerset',
            'property' => 'title',
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
