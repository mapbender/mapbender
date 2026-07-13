<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Regex;
use Mapbender\CoreBundle\Element\SimpleSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SimpleSearchAdminConfigurationType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private TranslatorInterface $trans)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaults = SimpleSearch::getDefaultChildConfiguration();

        $builder
            ->add('title', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.title',
                'help' => 'mb.core.simplesearch.admin.title.help',
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ], $this->trans))
            ->add('placeholder', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.placeholder',
                'help' => 'mb.core.simplesearch.admin.placeholder.help',
                'required' => false,
            ], $this->trans))
            ->add('query_url', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.query_url',
                'help' => 'mb.core.simplesearch.admin.query_url.help',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Url()
                ],
            ], $this->trans))
            ->add('query_key', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.query_key',
                'help' => 'mb.core.simplesearch.admin.query_key.help',
                'required' => true,
                 'constraints' => [
                    new NotBlank(),
                ],
            ], $this->trans))
            ->add('query_ws_replace', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.query_ws_replace',
                'help' => 'mb.core.simplesearch.admin.query_ws_replace.help',
                'trim' => false,
                'required' => false,
            ], $this->trans))
            ->add('query_format', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.query_format',
                'help' => 'mb.core.simplesearch.admin.query_format.help',
                'required' => true,
                 'constraints' => [
                    new NotBlank(),
                    new Regex(pattern: '#.*%.*#', message: 'mb.core.simplesearch.errors.invalid_query_format'),
                ],
            ], $this->trans))
            ->add('token_regex', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.token_regex',
                'help' => 'mb.core.simplesearch.admin.token_regex.help',
                'required' => false,
            ], $this->trans))
            ->add('token_regex_in', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.token_regex_in',
                'help' => 'mb.core.simplesearch.admin.token_regex_in.help',
                'required' => false,
            ], $this->trans))
            ->add('token_regex_out', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.token_regex_out',
                'help' => 'mb.core.simplesearch.admin.token_regex_out.help',
                'required' => false,
            ], $this->trans))
            ->add('collection_path', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.collection_path',
                'help' => 'mb.core.simplesearch.admin.collection_path.help',
                'required' => false,
            ], $this->trans))
            ->add('label_attribute', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.label_attribute',
                'help' => 'mb.core.simplesearch.admin.label_attribute.help',
                'required' => true,
                 'constraints' => [
                    new NotBlank(),
                ],
            ], $this->trans))
            ->add('geom_attribute', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.geom_attribute',
                'help' => 'mb.core.simplesearch.admin.geom_attribute.help',
                'required' => true,
                 'constraints' => [
                    new NotBlank(),
                ],
            ], $this->trans))
            ->add('geom_format', ChoiceType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.geom_format',
                'help' => 'mb.core.simplesearch.admin.geom_format.help',
                'choices' => [
                    'WKT' => 'WKT',
                    'GeoJSON' => 'GeoJSON',
                ],
                'required' => true,
            ], $this->trans))
            ->add('sourceSrs', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.sourceSrs',
                'help' => 'mb.core.simplesearch.admin.sourceSrs.help',
                'constraints' => [
                    new Regex(pattern: '#^EPSG:\d+$#', message: 'mb.core.simplesearch.errors.invalid_epsg_code')
                ],
                'attr' => [
                    'placeholder' => $defaults['sourceSrs'],
                ],
                'empty_data' => $defaults['sourceSrs'],
                'required' => false,
            ], $this->trans))
            ->add('delay', NumberType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.delay',
                'help' => 'mb.core.simplesearch.admin.delay.help',
                'required' => false,
            ], $this->trans))
            ->add('result_buffer', NumberType::class, $this->createInlineHelpText([
                    'label' => 'mb.core.simplesearch.admin.result_buffer',
                    'help' => 'mb.core.simplesearch.admin.result_buffer.help',
                    'required' => false,
                ]
                , $this->trans))
            ->add('result_minscale', NumberType::class, $this->createInlineHelpText([
                    'label' => 'mb.core.simplesearch.admin.result_minscale',
                    'help' => 'mb.core.simplesearch.admin.result_minscale.help',
                    'required' => false,
                ]
                , $this->trans))
            ->add('result_maxscale', NumberType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.result_maxscale',
                'help' => 'mb.core.simplesearch.admin.result_minscale.help',
                'required' => false,
            ], $this->trans))
            ->add('result_icon_url', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.result_icon_url',
                'help' => 'mb.core.simplesearch.admin.result_icon_url.help',
                'required' => false,
            ], $this->trans))
            ->add('result_icon_offset', TextType::class, $this->createInlineHelpText([
                'label' => 'mb.core.simplesearch.admin.result_icon_offset',
                'help' => 'mb.core.simplesearch.admin.result_icon_offset.help',
                'required' => false,
            ], $this->trans))
        ;
    }
}
