<?php


namespace Mapbender\CoreBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RewriteFormThemeCompilerPass implements CompilerPassInterface
{
    /** @var string, twig template reference */
    protected $fromTheme;
    /** @var string, twig template reference */
    protected $toTheme;

    public function __construct($fromTheme, $toTheme)
    {
        $this->fromTheme = $fromTheme;
        $this->toTheme = $toTheme;
    }

    public function process(ContainerBuilder $container)
    {
        $this->patchTwigThemes($container, $this->fromTheme, $this->toTheme);
        $this->patchResourceReferenceArray($container, 'twig.form.resources', $this->fromTheme, $this->toTheme);
    }

    public static function patchTwigThemes(ContainerBuilder $container, $from, $to)
    {
        $definition = $container->getDefinition('twig');
        $initializer = $definition->getArgument(1);
        if (array_key_exists('form_themes', $initializer)) {
            $themeConfig = &$initializer['form_themes'];
            foreach (array_keys($themeConfig) as $index) {
                if ($themeConfig[$index] === $from) {
                    $themeConfig[$index] = $to;
                }
            }
            $initializer['form_themes'] = $themeConfig;
        }
        $definition->replaceArgument(1, $initializer);
    }

    public static function patchResourceReferenceArray(ContainerBuilder $container, $parameterKey, $from, $to)
    {
        $parameterValue = $container->getParameter($parameterKey);
        foreach (array_keys($parameterValue) as $index) {
            if ($parameterValue[$index] == $from) {
                $parameterValue[$index] = $to;
            }
        }
        $container->setParameter($parameterKey, $parameterValue);
    }
}
