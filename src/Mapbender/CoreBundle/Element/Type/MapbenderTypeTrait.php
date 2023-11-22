<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Contracts\Translation\TranslatorInterface;

trait MapbenderTypeTrait
{
    /**
     * Modify a configuration for a form type to render a question mark icon next to the input field
     * that shows the 'help' text after clicking on it
     * If the 'help' key is not set, the given configuration is returned unmodified
     * @param array $typeConfiguration
     * @return array
     */
    public function createInlineHelpText(array $typeConfiguration, TranslatorInterface $trans): array
    {
        if (empty($typeConfiguration['help'])) return $typeConfiguration;
        $typeConfiguration['help'] = '<i class="fas fa-question-circle" data-bs-toggle="popover" data-bs-content="'
            . htmlspecialchars( $trans->trans($typeConfiguration['help']))
            . '" data-bs-placement="left" data-bs-html="true"></i>';
        $typeConfiguration['help_html'] = true;
        $helpAttr = array_key_exists('help_attr', $typeConfiguration) ? $typeConfiguration['help_attr'] : [];
        $typeConfiguration['help_attr'] = $helpAttr;
        return $typeConfiguration;
    }
}
