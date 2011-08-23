<?php

namespace Mapbender\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Generator\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class InitElementCommand extends ContainerAwareCommand {
    protected function configure() {
        $this->setDefinition(array(
                new InputArgument('bundle', InputArgument::REQUIRED, 'The bundle namespace of the Element to create'),
                new InputArgument('classname', InputArgument::REQUIRED, 'The classname of the Element to create'),
                new InputArgument('dir', InputArgument::REQUIRED, 'The directory where to find the bundle'),
                new InputOption('type', '', InputOption::VALUE_REQUIRED, 'Type of Element to create (general, button, map-click, map-box)', 'general')
            ))
            ->setHelp(<<<EOT
The <info>mapbender:init:element</info> command generates a new Mapbender element with a basic skeleton.

<info>./app/console/ mapbender:init:element "Vendor\HelloBundle" MyElement src </info>

The generated Element class will be Vendor\HelloBundle\Element\MyElement.
EOT
            )
            ->setName('mapbender:init:element');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $bundleNamespace = $input->getArgument('bundle');
        $className = $input->getArgument('classname');
        $classNameLower = strtolower($className);
        $type = $input->getOption('type');

        if(!in_array($type, array(
            'general',
            'button',
            'map-click',
            'map-box'))) {
            throw new \RuntimeException(sprintf('The element type "%s" is not supported.', $type));
        }

        //TODO: Type validation
        //TODO: Does this work?
        if (preg_match('[^A-Za-z0-9]', $className)) {
            throw new \InvalidArgumentException('The classname contains invalid characters.');
        }
        // validate namespace
        $bundleNamespace = strtr($bundleNamespace, '/', '\\');
        if (preg_match('/[^A-Za-z0-9_\\\-]/', $bundleNamespace)) {
            throw new \InvalidArgumentException('The bundle namespace contains invalid characters.');
        }

        // validate that the namespace is at least one level deep
        if (false === strpos($bundleNamespace, '\\')) {
            $msg = array();
            $msg[] = sprintf('The namespace must contain a vendor namespace (e.g. "VendorName\%s" instead of simply "%s").', $bundle, $bundle);
            $msg[] = 'If you\'ve specified a vendor namespace, did you forget to surround it with quotes (mapbender:init:element "Acme\BlogBundle")?';

            throw new \InvalidArgumentException(implode("\n\n", $msg));
        }

        $dir = $input->getArgument('dir');

        // add trailing / if necessary
        $dir = '/' === substr($dir, -1, 1) ? $dir : $dir.'/';
        $bundleDir = $dir.strtr($bundleNamespace, '\\', '/');
        $bundle = strtr($bundleNamespace, array('\\' => ''));

        if (!file_exists($bundleDir)) {
            throw new \RuntimeException(sprintf('Bundle directory "%s" does not exist.', $bundleDir));
        }

        // Copy skeleton files
        $filesystem = $this->container->get('filesystem');

        $classFile = sprintf('%s/Element/%s.php', $bundleDir, $className);
        $widgetFile = sprintf('%s/Resources/public/mapbender.element.%s.js', $bundleDir, $classNameLower);
        $twigFile = sprintf('%s/Resources/views/Element/%s.html.twig', $bundleDir, $classNameLower);
        if(file_exists($classFile) || file_exists($widgetFile) || file_exists($twigFile)) {
            $msg = array();
            $msg[] = "One of the the following files already exists and would be overwritten. Aborting.";
            $msg[] = $classFile;
            $msg[] = $twigFile;
            $msg[] = $widgetFile;
            $msg = implode("\n", $msg);
            throw new \RuntimeException($msg);
        }

        $filesystem->copy(__DIR__ . '/../Resources/skeleton/element/' . $type . '.php',
            $classFile);
        Generator::renderFile($classFile, array(
            'bundleNamespace' => $bundleNamespace,
            'className' => $className,
            'classNameLower' => $classNameLower,
            'bundle' => $bundle,
        ));

        $filesystem->copy(__DIR__ . '/../Resources/skeleton/element/widget-' . $type . '.js',
            $widgetFile);
        Generator::renderFile($widgetFile, array(
            'widgetName' => $className
        ));

        if($type === 'general') {
            $filesystem->copy(__DIR__ . '/../Resources/skeleton/element/widget-' . $type . '.html.twig',
                $twigFile);
            Generator::renderFile($twigFile, array(
                'classNameLower' => $classNameLower
            ));
        }

        $output->writeln('<comment>Summary of actions</comment>');
        $output->writeln(sprintf('- Your element %s\Element\%s has been created.', $bundle, $className));
        $output->writeln('- A PHP class, Twig template and jQuery widget have been created:');
        $output->writeln('  - ' . $classFile);
        if($type === 'general') {
            $output->writeln('  - ' . $twigFile);
        }
        $output->writeln('  - ' . $widgetFile);
        $output->writeln('');
        $output->writeln('<comment>Follow up actions</comment>');
        $output->writeln('Read about adapting your bare-bone element at <info>http://mapbender.org/3/cookbook/element-from-skeleton</info>');
        //TODO: SUmmary
    }
}

