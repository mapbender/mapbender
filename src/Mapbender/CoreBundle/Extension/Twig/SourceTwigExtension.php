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

    public function getFilters()
    {
        return [
            new TwigFilter('source_label', [$this, 'getSourceLabel']),
        ];
    }

    function getSourceLabel(string $type): ?string
    {
        $source = $this->typeDirectoryService->getSource($type);
        return $source->getLabel(true);
    }

}
