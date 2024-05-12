<?php


namespace Mapbender\CoreBundle\Element\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CoordinatesUtilitySrsListType extends AbstractType implements DataTransformerInterface
{
    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function getParent(): string
    {
        return 'Symfony\Component\Form\Extension\Core\Type\TextType';
    }

    public function getBlockPrefix(): string
    {
        return 'srslist';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'attr' => array(
                'class' => 'srs-autocomplete',
                'data-autocomplete-url' => $this->urlGenerator->generate('srs_autocomplete'),
            ),
        ));
    }

    /**
     * Transform norm data to view data
     */
    public function transform($value): ?string
    {
        if (!$value) {
            return null;
        }
        if (!\is_array($value)) {
            throw new TransformationFailedException("Expected array, got " . gettype($value));
        }
        $parts = array();
        foreach ($value as $srsInfo) {
            if (!empty($srsInfo['title'])) {
                $parts[] = "{$srsInfo['name']} | {$srsInfo['title']}";
            } else {
                $parts[] = $srsInfo['name'];
            }
        }
        return implode(', ', $parts);
    }

    /**
     * Transform view data to norm data
     * @param mixed $value
     * @return mixed|void
     */
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return null;
        }
        if (!\is_string($value)) {
            throw new TransformationFailedException("Expected string, got " . gettype($value));
        }
        $inputs = array_filter(array_map('\trim', explode(',', $value)), '\strlen');
        $srsDefs = array();
        foreach ($inputs as $srsInput) {
            $srsInputParts = explode('|', $srsInput, 2);
            $srsName = trim($srsInputParts[0]);
            if (count($srsInputParts) === 2) {
                $srsTitle = trim($srsInputParts[1]) ?: '';
            } else {
                $srsTitle = '';
            }
            if (!empty($srsName)) {
                $srsDefs[] = array(
                    'name' => $srsName,
                    'title' => $srsTitle,
                );
            }
        }
        return $srsDefs;
    }
}
