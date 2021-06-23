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
                return $choices;
            },
        ));
    }
}
