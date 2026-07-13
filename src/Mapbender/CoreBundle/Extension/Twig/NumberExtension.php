<?php


namespace Mapbender\CoreBundle\Extension\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class NumberExtension extends AbstractExtension
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getName(): string
    {
        return 'mbcore_number';
    }

    public function getFilters(): array
    {
        return [
            'formatted_number' => new TwigFilter('formatted_number', $this->formatNumber(...)),
        ];
    }

    public function formatNumber($number): string|false
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        return \NumberFormatter::create($locale, \NumberFormatter::DECIMAL)->format($number);
    }
}
