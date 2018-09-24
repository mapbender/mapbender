<?php


namespace Mapbender\WmsBundle\Element\Type;


use Symfony\Component\Form\AbstractType;

/**
 * This might seem like it doesn't do anything, but it adds the slider and a non-submitting extent
 * display field via custom twig skin.
 * See Resources/views/form/fields.html.twig
 *
 * For client-side interactions relying on this type + skin see Resources/public/backend/dimensionhandler.js
 */
class DimensionSetExtentType extends AbstractType
{
    public function getParent()
    {
        return 'hidden';
    }
}
