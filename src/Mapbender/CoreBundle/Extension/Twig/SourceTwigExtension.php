<?php

namespace Mapbender\CoreBundle\Extension\Twig;


use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SourceTwigExtension extends AbstractExtension
{
    public function __construct(protected TypeDirectoryService $typeDirectoryService)
    {

    }

    public function getName()
    {
        return 'mbcore_source';
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('source_label', [$this, 'getSourceLabel']),
            new TwigFilter('source_label_long', [$this, 'getSourceLabelLong']),
            new TwigFilter('source_accent_color', [$this, 'getSourceAccentColor']),
        ];
    }

    function getSourceLabel(string $type): ?string
    {
        $source = $this->typeDirectoryService->getSource($type);
        return $source->getLabel(true);
    }

    function getSourceLabelLong(string $type): ?string
    {
        $source = $this->typeDirectoryService->getSource($type);
        return $source->getLabel(false);
    }

    function getSourceAccentColor(string $type): string
    {
        $source = $this->typeDirectoryService->getSource($type);
        return $source->getAccentColor();
    }

}
