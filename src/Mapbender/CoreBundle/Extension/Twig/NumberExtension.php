<?php


namespace Mapbender\CoreBundle\Extension\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class NumberExtension extends AbstractExtension
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getName()
    {
        return 'mbcore_number';
    }

    public function getFilters()
    {
        return array(
            'formatted_number' => new TwigFilter('formatted_number', [$this, 'formatNumber']),
        );
    }

    public function formatNumber($number)
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        return \NumberFormatter::create($locale, \NumberFormatter::DECIMAL)->format($number);
    }
}
