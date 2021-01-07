<?php


namespace Mapbender\CoreBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wheregroup\SasscBinaries\Binary;

class AutodetectSasscBinaryPass implements CompilerPassInterface
{
    /** @var string */
    protected $parameterName;

    /**
     * @param string $parameterName
     */
    public function __construct($parameterName)
    {
        $this->parameterName = $parameterName;
    }

    public function process(ContainerBuilder $container)
    {
        $paramValue = $container->getParameter($this->parameterName);
        if (empty($paramValue)) {
            $replacement = Binary::pick();
        } elseif (!is_executable($paramValue)) {
            // NOTE: E_USER_DEPRECATED is the only error type that is guaranteed to go to the logs
            $message = "WARNING: configured sassc binary " . print_r($paramValue, true) . " not found or not executable.";
            @trigger_error($message, E_USER_DEPRECATED);
            if (\php_sapi_name() === 'cli') {
                fwrite(STDERR, $message . "\n");
            }
            $replacement = Binary::pick();
        } else {
            $replacement = null;
        }
        if ($replacement) {
            if (!is_executable($replacement)) {
                throw new \LogicException("No execution privileges on sassc binary " . print_r($replacement, true));
            }
            $container->setParameter($this->parameterName, $replacement);
        }
    }
}
