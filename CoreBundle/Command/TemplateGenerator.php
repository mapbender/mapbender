<?php

namespace Mapbender\CoreBundle\Command;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Class TemplateGenerator
 *
 * @deprecated should be removed in release/3.0.6
 */
class TemplateGenerator extends Generator {
    public function create($container, $bundle, $bundleDir, $bundleNamespace, $className, $type = null) {
        $files = Array();

        $classNameLower = strtolower($className);

        // Copy skeleton files
        $filesystem = $container->get('filesystem');

        $files = array(
            'class' => sprintf('%s/Template/%s.php', $bundleDir, $className),
            'js' => sprintf('%s/Resources/public/mapbender.template.%s.js',
                $bundleDir, $classNameLower),
            'css' => sprintf('%s/Resources/public/mapbender.template.%s.js',
                $bundleDir, $classNameLower),
            'twig' => sprintf('%s/Resources/views/Template/%s.html.twig',
                $bundleDir, $classNameLower));

        $exists = array();
        foreach($files as $type => $file) {
            if(file_exists($file)) {
                $exists[] = $file;
            }
        }
        if(count($exists) > 0) {
            $msg = array();
            $msg[] = 'The following file(s) exist and would be overwritten. '
                .'Aborting.';
            $msg = array_merge($msg, $exists);
            throw new \RuntimeException($msg);
        }

        foreach($files as $type => $file) {
            $skeletonFile = pathinfo($file, PATHINFO_FILENAME);
            $this->renderFile(__DIR__ . '/../Resources/skeleton/template',
                $skeletonFile, array(
                    'bundleNamespace' => $bundleNamespace,
                    'className' => $className,
                    'classNameLower' => $classNameLower,
                    'bundle' => $bundle));
        }

        return $files;
    }
}
