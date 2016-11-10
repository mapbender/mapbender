<?php

namespace Mapbender\CoreBundle\Command;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Class ElementGenerator
 *
 * @deprecated should be removed in release/3.0.6
 */
class ElementGenerator extends Generator
{
    public function create($container, $bundle, $bundleDir, $bundleNamespace, $className, $type)
    {
        $files = array();
        $classNameLower = strtolower($className);

        // Copy skeleton files
        $filesystem = $container->get('filesystem');

        $classFile  = sprintf('%s/Element/%s.php', $bundleDir, $className);
        $widgetFile = sprintf('%s/Resources/public/mapbender.element.%s.js', $bundleDir, $classNameLower);
        $twigFile   = sprintf('%s/Resources/views/Element/%s.html.twig', $bundleDir, $classNameLower);

        if (file_exists($classFile) || file_exists($widgetFile) || file_exists($twigFile)) {
            $msg   = array();
            $msg[] = "One of the the following files already exists and would be overwritten. Aborting.";
            $msg[] = $classFile;
            $msg[] = $twigFile;
            $msg[] = $widgetFile;
            $msg   = implode("\n", $msg);
            throw new \RuntimeException($msg);
        }

        $this->setSkeletonDirs(__DIR__ . '/../Resources/skeleton/element');

        $this->renderFile(
            $type . '.php.twig',
            $classFile,
            array(
                'bundleNamespace' => $bundleNamespace,
                'className'       => $className,
                'classNameLower'  => $classNameLower,
                'bundle'          => $bundle));
        $files['PHP class'] = $classFile;

        $this->renderFile(
            $type . '.js.twig',
            $widgetFile,
            array(
                'widgetName' => $className));
        $files['jQuery widget'] = $widgetFile;

        if ($type === 'general') {
            $this->renderFile(
                $type . '.html.twig',
                $twigFile,
                array(
                    'classNameLower' => $classNameLower));
            $files['Twig template'] = $twigFile;
        }

        return $files;
    }
}
