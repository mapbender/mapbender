<?php


namespace Mapbender\CoreBundle\Form\Type;


use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderAwareMultipleChoiceType extends ChoiceType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        /**
         * Prevent addition of order-destructive MergeCollectionListener
         * @see ChoiceType::buildForm() L150
         */
        $resolver->setDefaults(array(
            'by_reference' => false,
        ));
        $resolver->setAllowedValues('by_reference', array(false));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $eventDispatcher = $builder->getEventDispatcher();
        $preSubmitHandlersBefore = $eventDispatcher->getListeners(FormEvents::PRE_SUBMIT);
        parent::buildForm($builder, $options);
        if ($options['expanded'] && $options['multiple']) {
            $preSubmitHandlersAfter = $eventDispatcher->getListeners(FormEvents::PRE_SUBMIT);
            /**
             * Remove and replace the low-priority PRE_SUBMIT handler closure registered at
             * @see ChoiceType::buildForm() at line 89. Ignore the high-priority PRE_SUBMIT handler
             * closure registered at line 158.
             * getListeners returns in priority order, so we just go in reverse (low-to-high)
             * and drop the first newly registered handler.
             */
            foreach (array_reverse($preSubmitHandlersAfter) as $preSubmitHandler) {
                if (!\in_array($preSubmitHandler, $preSubmitHandlersBefore, true)) {
                    $eventDispatcher->removeListener(FormEvents::PRE_SUBMIT, $preSubmitHandler);
                    // Register replacement
                    $eventDispatcher->addListener(FormEvents::PRE_SUBMIT, array($this, 'preSubmit'));
                    break;
                }
            }
            $eventDispatcher->addListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        }
    }

    /**
     * Reimplementation of first PRE_SUBMIT handler added by ChoiceType, to respect submitted
     * form data order.
     *
     * @see ChoiceType::buildForm()
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        // Since the type always use mapper an empty array will not be
        // considered as empty in Form::submit(), we need to evaluate
        // empty data here so its value is submitted to sub forms
        if (null === $data) {
            $data = $form->getConfig()->getEmptyData();
            if ($data instanceof \Closure) {
                $data = $data($form, $form->getData());
            }
        }

        // Convert the submitted data to a string, if scalar, before
        // casting it to an array
        if (!\is_array($data)) {
            $data = array(strval($data));
        }
        // Keep data values as they are, but reubild the array using form children names
        // as keys.
        $formChildMap = array();
        foreach ($form->all() as $child) {
            $formValue = $child->getConfig()->getOption('value');
            $formChildMap[$formValue] = $child;
        }
        $reconstructedData = array();
        $reconstructedCheckboxes = array();
        $unknownValues = array_flip($data);
        foreach ($data as $index => $entryValue) {
            if (isset($formChildMap[$entryValue])) {
                $checkbox = $formChildMap[$entryValue];
                $reconstructedData[$checkbox->getName()] = $entryValue;
                $reconstructedCheckboxes[] = $checkbox;
                unset($unknownValues[$entryValue]);
            }
        }
        unset($unknownValues['']);
        // Throw exception if unknown values were submitted
        if (\count($unknownValues) > 0) {
            throw new TransformationFailedException(sprintf('The choices "%s" do not exist in the choice list.', implode('", "', array_keys($unknownValues))));
        }
        // Reorder checbox-type form children to match submitted data order
        // This prevents another re-destruction pass of data order in the default data mapper
        /** @see \Symfony\Component\Form\Extension\Core\DataMapper\CheckboxListMapper */
        foreach ($formChildMap as $checkboxValue => $checkbox) {
            if ($checkboxValue != '') {
                $form->remove($checkbox->getName());
            }
        }
        foreach ($reconstructedCheckboxes as $checkbox) {
            $form->add($checkbox);
        }
        $event->setData($reconstructedData);
    }

    public function preSetData(FormEvent $event)
    {
        // Rebuild child checkbox list in order of current data
        $form = $event->getForm();
        $formChildMap = array();
        foreach ($form->all() as $child) {
            $formValue = $child->getConfig()->getOption('value');
            $formChildMap[$formValue] = $child;
            $form->remove($child->getName());
        }
        foreach ($event->getData() as $selectedValue) {
            if (isset($formChildMap[$selectedValue])) {
                $selectedChoice = $formChildMap[$selectedValue];
                $form->add($selectedChoice);
            }
            unset($formChildMap[$selectedValue]);
        }
        // also re-add remaining (not currently selected) choices
        foreach ($formChildMap as $ch) {
            $form->add($ch);
        }
    }
}
