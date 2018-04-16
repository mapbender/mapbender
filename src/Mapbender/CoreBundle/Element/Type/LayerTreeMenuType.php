<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LayerTreeMenuType extends AbstractType
{
    protected $exposedChoices = array();

    /**
     * @param boolean $showDimensions see services.xml, controlled via parameter `mapbender.preview.element.dimensionshandler`
     */
    public function __construct($showDimensions)
    {
        $this->exposedChoices = array(
            "layerremove" => "Remove layer",
            "opacity" => "Opacity",
            "zoomtolayer" => "Zoom to layer",
            "metadata" => "Metadata",
        );
        if ($showDimensions) {
            $this->exposedChoices += array(
                "dimension" => "Dimension",
            );
        }
    }


    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->exposedChoices,
            'multiple' => true,
        ));
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'layertree_menu';
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return 'choice';
    }
}
