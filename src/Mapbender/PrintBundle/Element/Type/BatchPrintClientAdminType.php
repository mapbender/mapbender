<?php
namespace Mapbender\PrintBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Admin form type for BatchPrintClient element configuration
 */
class BatchPrintClientAdminType extends PrintClientAdminType
{

    public function __construct(bool $queueable)
    {
        parent::__construct($queueable);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        
        // Add batch print specific configuration alongside other checkboxes
        $builder
            ->add('enableGeofileUpload', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.admin.batchprintclient.label.enableGeofileUpload',
            ))
        ;
    }
}
