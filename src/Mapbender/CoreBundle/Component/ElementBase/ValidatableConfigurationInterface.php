<?php

namespace Mapbender\CoreBundle\Component\ElementBase;

use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

interface ValidatableConfigurationInterface
{
    /**
     * Validate the configuration here. The method is called in two cases:
     * - when saving a form in the administration backend. The `$form` attribute will be non-null.
     *   You should create a form error in this case, e.g.
     *   `$form->get('configuration')->get('mykey')->addError(new FormError('Something went wrong'));`
     * - when accessing an application in the frontend. In this case, the `$form` argument will be null.
     *   Throw a ValidationFailedException` if a validation error occurs.
     *   Caution: This message will be shown to frontend users.
     * @throws ValidationFailedException
     */
    public static function validate(array $configuration, FormInterface $form, TranslatorInterface $translator): void;
}

class ValidationFailedException extends \RuntimeException
{

}
