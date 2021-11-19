<?php


namespace Mapbender\FrameworkBundle\EventListener;


use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;


class AutomaticLocaleListener
{
    /** @var bool */
    protected $enabled;

    /**
     * @param bool $enableAutomaticLocale
     */
    public function __construct($enableAutomaticLocale)
    {
        $this->enabled = $enableAutomaticLocale;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($this->enabled && $event->getRequestType() !== HttpKernelInterface::SUB_REQUEST) {
            $request = $event->getRequest();
            $accept = $request->headers->get('Accept-Language', '');
            $languages = \array_filter(explode(',', $accept));
            foreach ($languages as $language) {
                $parts = explode(';', $language);   // Split off "q=" weighting factor
                if ($parts[0] && \preg_match('#^(de|en|es|fr|it|tr|ru)#i', $parts[0])) {
                    try {
                        $request->setLocale($parts[0]);
                    } catch (\Symfony\Polyfill\Intl\Icu\Exception\MethodNotImplementedException $e) {
                        // ignore
                    }
                    break;
                }
            }
        }
    }
}
